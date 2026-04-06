from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import pickle
import joblib
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
            scaler_x_path = os.path.join(BASE_DIR,"scaler_x_lstm.pkl",  f"{station}_scaler_X.pkl")



            if not os.path.exists(scaler_x_path):
                return json_error(f"Scaler X introuvable pour la station : {station_safe} (Chemin cherché: {scaler_x_path})", 404)

            with open(scaler_x_path, "rb") as f:
                scaler_X = joblib.load(f)

            # 2. Chargement dynamique du Scaler Y (si vous l'avez structuré pareil en dossier)
            scaler_y_path = os.path.join(BASE_DIR,"scaler_y_lstm.pkl",  f"{station}_scaler_Y.pkl")
            if os.path.exists(scaler_y_path):
                with open(scaler_y_path, "rb") as f:
                    scaler_y = joblib.load(f)
            else:
                # Si scaler_y_lstm.pkl est resté un simple fichier global
                global_scaler_y_path = os.path.join(BASE_DIR, "scaler_y_lstm.pkl")
                if os.path.isfile(global_scaler_y_path):
                    with open(global_scaler_y_path, "rb") as f:
                        scaler_y = pickle.load(f)
                else:
                    scaler_y = None

            # 3. Chargement du modèle Keras
            model_path = os.path.join(LSTM_DIR,"modele_lstm.keras", f"{station}_lstm_24h.keras")
            if not os.path.exists(model_path):
                return json_error(f"Modèle LSTM introuvable pour la station : {station}", 404)

            model = load_model(model_path)

            # ----------------------------------------------------
            # LECTURE ET PRÉPARATION DU FICHIER SYNOP COMPLET
            # ----------------------------------------------------
            synop_path = os.path.join(BASE_DIR, "synop_2026.csv")
            
            if not os.path.exists(synop_path):
                return json_error(f"Fichier de données global introuvable : {synop_path}", 404)
                
            # 1. On lit avec la VIRGULE comme séparateur
            df_global = pd.read_csv(synop_path, sep=",", low_memory=False) 
            
            # 2. On filtre la station (la colonne s'appelle 'name' dans votre CSV)
            if 'name' in df_global.columns:
                df_station = df_global[df_global['name'] == station].copy()
            else:
                return json_error("La colonne 'name' est introuvable dans le CSV.", 500)

            if df_station.empty:
                return json_error(f"Aucune donnée trouvée pour la station {station}", 404)

            # 3. RECRÉATION DES COLONNES MANQUANTES (Feature Engineering)
            
            # Temps (Heure en Sinus/Cosinus)
            df_station['date_time'] = pd.to_datetime(df_station['reference_time'])
            df_station['hour'] = df_station['date_time'].dt.hour
            df_station['hr_sin'] = np.sin(2 * np.pi * df_station['hour'] / 24)
            df_station['hr_cos'] = np.cos(2 * np.pi * df_station['hour'] / 24)

            # Vent (Décomposition en vecteurs U et V à partir de direction 'dd' et force 'ff')
            df_station['dd'] = pd.to_numeric(df_station['dd'], errors='coerce').fillna(0)
            df_station['ff'] = pd.to_numeric(df_station['ff'], errors='coerce').fillna(0)
            df_station['wind_u'] = -df_station['ff'] * np.sin(np.pi * df_station['dd'] / 180)
            df_station['wind_v'] = -df_station['ff'] * np.cos(np.pi * df_station['dd'] / 180)

            # Point de rosée ('td' dans Météo-France)
            df_station['dew_point'] = df_station['td']

            # Résidu (Si vous avez utilisé une moyenne mobile lors de l'entraînement, on simule ici par 0 pour simplifier)
            df_station['residue'] = 0

            # Nettoyage des autres colonnes (remplacer les valeurs manquantes par 0)
            for col in ['u', 'pres', 'pmer', 'tend', 'n', 'rr1', 'dew_point']:
                df_station[col] = pd.to_numeric(df_station[col], errors='coerce').fillna(0)

            # 4. SÉLECTION FINALE
            colonnes_requises = [
                'hr_sin', 'hr_cos', 'residue', 'u', 'pres', 'wind_u', 
                'wind_v', 'dew_point', 'pmer', 'tend', 'n', 'rr1'
            ]
            
            df_historique = df_station[colonnes_requises].tail(80)
                
            if len(df_historique) < 80:
                return json_error(f"Pas assez de données pour {station}. Lignes: {len(df_historique)}/80.", 400)
                
            # 5. On envoie au Scaler puis au modèle
            X_real = df_historique.values
            X_scaled_flat = scaler_X.transform(X_real)
            X_scaled = X_scaled_flat.reshape(1, 80, 12)

            pred_scaled = model.predict(X_scaled)
            # ----------------------------------------------------
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