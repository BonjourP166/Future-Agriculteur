<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Projet Time Series - Météo Montpellier</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; line-height: 1.6; max-width: 900px; margin: auto; padding: 20px; background-color: #f4f7f6; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .badge { background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8em; margin-right: 5px; margin-bottom: 20px; display: inline-block; }
        .intro { margin-bottom: 20px; color: #555; }
    </style>
</head>
<body>

<div class="container">
    <h1>Prédiction Temporelle d'un an (Mensuel) par SARIMA</h1>
    
    <div>
        <span class="badge">Python</span>
        <span class="badge">Statsmodels</span>
        <span class="badge">Pandas</span>
    </div>

    <p class="intro">Ce script illustre la préparation de données météorologiques réelles et l'utilisation d'un modèle <strong>SARIMA(1,1,1)(0,1,1,12)</strong>. Les hyperparamètres ont été préalablement optimisés via un Grid Search (minimisation de la RMSE et de l'AIC) pour prédire au mieux la température de l'année à venir.</p>

    <pre><code class="language-python">
import warnings
warnings.filterwarnings("ignore")

import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAX

# =========================================================
# 1. CHARGEMENT ET PRÉPARATION DES DONNÉES
# =========================================================
# Récupération depuis la base de données active (objet 'bdd')
df = pd.read_sql("SELECT date, temperature FROM releve_meteo", bdd)

# Nettoyage et conversion des types
df["date"] = pd.to_datetime(df["date"], errors="coerce")
df = df.dropna(subset=["date", "temperature"]).sort_values("date")
df["temperature"] = df["temperature"] - 273.15 # Conversion Kelvin -> Celsius

# Agrégation mensuelle pour le modèle SARIMA
monthly = df.groupby(pd.Grouper(key="date", freq="ME"))["temperature"].mean().dropna()

# =========================================================
# 2. ENTRAÎNEMENT DU MODÈLE SARIMA OPTIMISÉ
# =========================================================
# Utilisation du meilleur modèle retenu : SARIMA(1,1,1)(0,1,1,12)
print("⚙️ Entraînement du modèle avec les hyperparamètres optimaux...")

final_model = SARIMAX(monthly, 
                      order=(1, 1, 1), 
                      seasonal_order=(0, 1, 1, 12),
                      enforce_stationarity=False, 
                      enforce_invertibility=False)

final_fit = final_model.fit(disp=False)
print("✅ Modèle entraîné avec succès !")

# =========================================================
# 3. PRÉDICTION (ANNÉE SUIVANTE)
# =========================================================
# Génération des prévisions sur 12 mois et des intervalles de confiance à 95%
forecast_obj = final_fit.get_forecast(steps=12)
forecast_mean = forecast_obj.predicted_mean
conf_int = forecast_obj.conf_int()

# Mise en forme du DataFrame final de résultats
future_dates = pd.date_range(start=monthly.index[-1] + pd.offsets.MonthEnd(1), periods=12, freq="ME")
prevision_df = pd.DataFrame({
    "date": future_dates,
    "prediction": forecast_mean.values,
    "borne_inf_95": conf_int.iloc[:, 0].values,
    "borne_sup_95": conf_int.iloc[:, 1].values
})

print(prevision_df.head())
    </code></pre>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
</body>
</html>