from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import pickle
import numpy as np
import pandas as pd
from tensorflow.keras.models import load_model

app = Flask(__name__)
CORS(app) 

# ==============================
# PATHS
# ==============================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Dossiers des modèles
LSTM_DIR = BASE_DIR
SARIMA_WEEKLY_DIR = os.path.join(BASE_DIR, "models_sarima_weekly")
SARIMA_MONTHLY_DIR = os.path.join(BASE_DIR, "models_sarima_monthly")

# Petit cache pour éviter de recharger les modèles SARIMA à chaque appel
MODEL_CACHE = {}

# ==============================
# FONCTIONS UTILITAIRES
# ==============================
def safe_station_name(station: str) -> str:
    """
    Normalise le nom de station pour correspondre aux noms de fichiers.
    Exemples :
    'MONTPELLIER-AEROPORT' -> 'MONTPELLIER_AEROPORT'
    """
    return station.strip().replace(" ", "_").replace("-", "_")

def json_error(message: str, code: int = 400):
    return jsonify({"error": message}), code

def get_cached_model(model_path: str):
    if model_path not in MODEL_CACHE:
        with open(model_path, "rb") as f:
            MODEL_CACHE[model_path] = pickle.load(f)
    return MODEL_CACHE[model_path]

def get_weekly_step(target_date: pd.Timestamp) -> int:
    """Calcule combien de semaines entre aujourd'hui et la date cible."""
    today = pd.Timestamp.today().normalize()
    target = pd.Timestamp(target_date).normalize()

    delta_days = (target - today).days
    step = (delta_days // 7) + 1

    return max(1, step)

def get_monthly_step(target_date: pd.Timestamp) -> int:
    """Calcule combien de mois entre aujourd'hui et la date cible."""
    today = pd.Timestamp.today().normalize()
    target = pd.Timestamp(target_date).normalize()

    months_diff = (target.year - today.year) * 12 + (target.month - today.month)
    step = months_diff + 1

    return max(1, step)

# ==============================
# ROUTE API
# ==============================
@app.route("/predict", methods=["GET"])
def predict():
    station = request.args.get("station")
    horizon = request.args.get("horizon")
    target_date_str = request.args.get("target_date")

    if not station or not horizon:
        return json_error("Les paramètres 'station' et 'horizon' sont requis.", 400)

    station = station.strip()
    horizon = horizon.strip().lower()
    station_safe = safe_station_name(station)

    # Date cible optionnelle
    if target_date_str:
        try:
            target_date = pd.to_datetime(target_date_str)
        except Exception:
            return json_error(
                "Format de 'target_date' invalide. Exemple attendu : 2026-04-15",
                400
            )
    else:
        target_date = pd.Timestamp.today().normalize()

    try:
        # ===================================
        # JOURNALIER → ITSM / LSTM
        # ===================================
        if horizon == "journalier":
            
            # 1. Chargement dynamique du Scaler X pour cette ville
            # On cherche dans le dossier 'scaler_x_lstm.pkl' le fichier de la ville
            # /!\ MODIFIEZ le nom du fichier si vos pkl s'appellent différemment
            scaler_x_path = os.path.join(BASE_DIR, "scaler_x_lstm.pkl", f"{station_safe}.pkl")
            
            # Si jamais vous les avez nommés "scaler_MONTPELLIER.pkl", décommentez la ligne du dessous :
            scaler_x_path = os.path.join(BASE_DIR, "scaler_x_lstm.pkl", f"scaler_{station_safe}.pkl")

            if not os.path.exists(scaler_x_path):
                return json_error(f"Scaler X introuvable pour la station : {station_safe} (Chemin cherché: {scaler_x_path})", 404)

            with open(scaler_x_path, "rb") as f:
                scaler_X = pickle.load(f)

            # 2. Chargement dynamique du Scaler Y (si vous l'avez structuré pareil en dossier)
            scaler_y_path = os.path.join(BASE_DIR, "scaler_y_lstm.pkl", f"{station_safe}.pkl")
            if os.path.exists(scaler_y_path):
                with open(scaler_y_path, "rb") as f:
                    scaler_y = pickle.load(f)
            else:
                # Si scaler_y_lstm.pkl est resté un simple fichier global
                global_scaler_y_path = os.path.join(BASE_DIR, "scaler_y_lstm.pkl")
                if os.path.isfile(global_scaler_y_path):
                    with open(global_scaler_y_path, "rb") as f:
                        scaler_y = pickle.load(f)
                else:
                    scaler_y = None

            # 3. Chargement du modèle Keras
            model_path = os.path.join(LSTM_DIR, f"{station_safe}_lstm_24h.keras")
            if not os.path.exists(model_path):
                return json_error(f"Modèle LSTM introuvable pour la station : {station}", 404)

            model = load_model(model_path)

            # IMPORTANT : Remplacer X_dummy par vos vraies données d'entrée
            X_dummy = np.zeros((1, 80, 1))
            X_scaled = scaler_X.transform(X_dummy.reshape(-1, 1)).reshape(1, 80, 1)

            pred_scaled = model.predict(X_scaled)
            
            if scaler_y:
                pred = scaler_y.inverse_transform(pred_scaled)[0][0]
            else:
                pred = pred_scaled[0][0]
                
            step = 1 # Par défaut pour le journalier

        # ===================================
        # HEBDOMADAIRE → SARIMA
        # ===================================
        elif horizon in ["weekly", "hebdomadaire"]:
            model_path = os.path.join(SARIMA_WEEKLY_DIR, f"sarima_weekly_{station_safe}.pkl")

            if not os.path.exists(model_path):
                return json_error(f"Modèle SARIMA hebdomadaire introuvable pour la station : {station}", 404)

            model = get_cached_model(model_path)
            step = get_weekly_step(target_date)
            forecast = model.get_forecast(steps=step)
            pred = forecast.predicted_mean.iloc[step - 1]

        # ===================================
        # MENSUEL → SARIMA
        # ===================================
        elif horizon in ["monthly", "mensuel"]:
            model_path = os.path.join(SARIMA_MONTHLY_DIR, f"sarima_monthly_{station_safe}.pkl")

            if not os.path.exists(model_path):
                return json_error(f"Modèle SARIMA mensuel introuvable pour la station : {station}", 404)

            model = get_cached_model(model_path)
            step = get_monthly_step(target_date)
            forecast = model.get_forecast(steps=step)
            pred = forecast.predicted_mean.iloc[step - 1]

        else:
            return json_error("Horizon invalide. Valeurs autorisées : journalier, hebdomadaire/weekly, mensuel/monthly.", 400)

        return jsonify({
            "station": station,
            "station_fichier": station_safe,
            "horizon": horizon,
            "target_date": pd.Timestamp(target_date).strftime("%Y-%m-%d"),
            "step_utilise": int(step) if horizon in ["weekly", "hebdomadaire", "monthly", "mensuel"] else None,
            "temperature_predite": float(pred)
        })

    except FileNotFoundError as e:
        return json_error(f"Fichier introuvable : {str(e)}", 404)
    except PermissionError as e:
        return json_error(f"Erreur de permission : {str(e)}", 500)
    except Exception as e:
        return json_error(f"Erreur serveur : {str(e)}", 500)

# ==============================
# ROUTE TEST
# ==============================
@app.route("/", methods=["GET"])
def home():
    return jsonify({
        "message": "API de prédiction météo active",
        "routes": {
            "predict_weekly": "/predict?station=MONTPELLIER-AEROPORT&horizon=weekly&target_date=2026-04-15",
            "predict_monthly": "/predict?station=MONTPELLIER-AEROPORT&horizon=monthly&target_date=2026-06-01",
            "predict_daily": "/predict?station=MONTPELLIER-AEROPORT&horizon=journalier"
        }
    })

# ==============================
# RUN
# ==============================
if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)