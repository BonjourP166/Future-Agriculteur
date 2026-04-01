<!DOCTYPE html>
<html lang="fr">
<?php 
include 'bd.php';
session_start();
?>
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Algorithmes ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/nav.css">
    <link rel="stylesheet" href="styles/algo.css">
    <link rel="stylesheet" href="styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'nav.php'; ?>

    <main>

        <!-- HERO -->
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>Algorithmes de prédiction</h1>
                <p>Découvrez les modèles utilisés pour prédire la température</p>
            </div>
        </section>

        <!-- CONTENU PRINCIPAL -->
        <section class="presentation-section">

            <!-- BLOC 1 — LSTM -->
            <div class="algo-block">
                <div class="algo-header">
                    <h1>Prédiction à 24h par LSTM</h1>
                    <div class="algo-tags">
                        <span class="tag tag-model"><i class="fas fa-brain"></i> LSTM</span>
                        <span class="tag tag-horizon"><i class="fas fa-clock"></i> Horizon : 24h</span>
                        <span class="tag tag-input"><i class="fas fa-database"></i> 12 variables d'entrée</span>
                    </div>
                    <p>
                        Ce script automatise le nettoyage, l'ingénierie de caractéristiques
                        (vent vectoriel, résidus saisonniers) et l'entraînement d'un modèle
                        <strong>LSTM</strong> pour prédire la température sur les 24 heures suivantes.
                    </p>
                </div>
                <div class="code-wrapper">
                    <div class="code-topbar">
                        <div class="code-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="code-filename">lstm_forecast.py</span>
                        <span class="code-lang-badge">Python</span>
                    </div>
                    <pre><code>
import pandas as pd
import numpy as np
import glob
import joblib
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.models import Sequential

# =========================================================
# 1. CHARGEMENT ET NETTOYAGE MASSIF
# =========================================================
print("⏳ Chargement des fichiers SYNOP...")
files = sorted(glob.glob("synop_*.csv"))
cols_physiques = ['validity_time', 't', 'u', 'pres', 'dd', 'ff', 'td', 'pmer', 'tend', 'tend24', 'n', 'rr1', 'name']

df_raw = pd.concat([pd.read_csv(f, sep=None, engine='python', usecols=cols_physiques) for f in files])
df_raw['ds'] = pd.to_datetime(df_raw['validity_time'], utc=True)
df = df_raw[df_raw['name'] == "MONTPELLIER-AEROPORT"].drop_duplicates('ds').sort_values('ds').copy()

df['y'] = df['t'] - 273.15
df['dew_point'] = df['td'] - 273.15
df['pres'] = pd.to_numeric(df['pres'], errors='coerce') / 100

cols_num = ['y', 'u', 'pres', 'dd', 'ff', 'dew_point', 'pmer', 'tend', 'tend24', 'n', 'rr1']
for col in cols_num:
    df[col] = pd.to_numeric(df[col], errors='coerce')

df = df.set_index('ds')[cols_num]
df['rr1'] = df['rr1'].fillna(0)
df = df.interpolate(method='linear').bfill()
df = df.resample('3h').interpolate(method='linear')

# =========================================================
# 2. FEATURE ENGINEERING AVANCÉ
# =========================================================
df['hour'], df['day_of_year'] = df.index.hour, df.index.dayofyear

normales_map = df.groupby(['day_of_year', 'hour'])['y'].mean()
df['y_normal'] = [normales_map.get((d, h)) for d, h in zip(df['day_of_year'], df['hour'])]
df['residue'] = df['y'] - df['y_normal']

df['wind_u'] = df['ff'] * np.cos(np.deg2rad(df['dd']))
df['wind_v'] = df['ff'] * np.sin(np.deg2rad(df['dd']))
df['hr_sin'] = np.sin(2 * np.pi * df['hour'] / 24)
df['hr_cos'] = np.cos(2 * np.pi * df['hour'] / 24)

features = ['hr_sin', 'hr_cos', 'residue', 'u', 'pres', 'wind_u', 'wind_v', 'dew_point', 'pmer', 'tend', 'n', 'rr1']
df = df.dropna(subset=features)

# =========================================================
# 3. SCALING ET SÉQUENÇAGE
# =========================================================
scaler_X, scaler_y = MinMaxScaler(), MinMaxScaler()
scaled_X = scaler_X.fit_transform(df[features])
scaled_y = scaler_y.fit_transform(df[['residue']])

joblib.dump(scaler_X, 'scaler_X.pkl')
joblib.dump(scaler_y, 'scaler_y.pkl')

def create_sequences(data_X, data_y, in_len=72, out_len=8):
    """Fenêtres glissantes : 72h passées → 24h futures"""
    X, y = [], []
    for i in range(len(data_X) - in_len - out_len):
        X.append(data_X[i:i+in_len])
        y.append(data_y[i+in_len : i+in_len+out_len, 0])
    return np.array(X), np.array(y)

X_data, y_data = create_sequences(scaled_X, scaled_y)
split = int(0.85 * len(X_data))
X_train, X_test = X_data[:split], X_data[split:]
y_train, y_test = y_data[:split], y_data[split:]

# =========================================================
# 4. ARCHITECTURE LSTM
# =========================================================
model = Sequential([
    Input(shape=(X_train.shape[1], X_train.shape[2])),
    LSTM(100, return_sequences=True),
    Dropout(0.2),
    LSTM(50),
    Dense(32, activation='relu'),
    Dense(8)   # 8 pas de temps = 24h
])

model.compile(optimizer='adam', loss='mse')
print("🚀 Entraînement en cours (12 variables en entrée)...")
model.fit(X_train, y_train, epochs=15, batch_size=64, validation_split=0.1, verbose=1)

# =========================================================
# 5. INFÉRENCE ET RECONSTRUCTION
# =========================================================
cutoff = pd.Timestamp("2025-12-31 21:00:00", tz='UTC')
last_window = scaler_X.transform(df[df.index <= cutoff].tail(72)[features]).reshape(1, 72, len(features))

pred_res_scaled = model.predict(last_window)
pred_residue = scaler_y.inverse_transform(pred_res_scaled).flatten()

future_dates = pd.date_range(cutoff + pd.Timedelta(hours=3), periods=8, freq='3h')
future_normales = [normales_map.get((d.dayofyear, d.hour)) for d in future_dates]
final_forecast = pred_residue + np.array(future_normales)

df_res = pd.DataFrame({'ds': future_dates, 'y_pred': final_forecast})
df_res.to_csv("forecast_lstm_24h.csv", index=False)
model.save("modele_montpellier_final.keras")
print("✅ Modèle sauvegardé. Prédictions → 'forecast_lstm_24h.csv'")
                    </code></pre>
                </div>
            </div>

            <!-- BLOC 2 — SARIMA -->
            <div class="algo-block">
                <div class="algo-header">
                    <h1>Prédiction hebdomadaire & mensuelle par SARIMA</h1>
                    <div class="algo-tags">
                        <span class="tag tag-model"><i class="fas fa-chart-line"></i> SARIMA</span>
                        <span class="tag tag-horizon"><i class="fas fa-clock"></i> Horizon : 7j / 30j</span>
                        <span class="tag tag-input"><i class="fas fa-calendar-alt"></i> Séries temporelles</span>
                    </div>
                    <p>
                        En fonction de la prédiction souhaitée, les hyperparamètres diffèrent.
                        Pour la prédiction <strong>hebdomadaire</strong>, le modèle retenu est
                        <strong>SARIMA(1,1,1)(0,1,1,52)</strong>.
                        Pour la prédiction <strong>mensuelle</strong>, le modèle retenu est
                        <strong>SARIMA(1,1,1)(0,1,1,12)</strong>.
                    </p>
                </div>
                <div class="code-wrapper">
                    <div class="code-topbar">
                        <div class="code-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="code-filename">sarima_forecast.py</span>
                        <span class="code-lang-badge">Python</span>
                    </div>
                    <pre><code>
import warnings
warnings.filterwarnings("ignore")

import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAX

# =========================================================
# 1. CHARGEMENT ET PRÉPARATION DES DONNÉES
# =========================================================
df = pd.read_sql("SELECT date, temperature FROM releve_meteo", bdd)

df["date"] = pd.to_datetime(df["date"], errors="coerce")
df = df.dropna(subset=["date", "temperature"]).sort_values("date")
df["temperature"] = df["temperature"] - 273.15   # Kelvin → Celsius

monthly = df.groupby(pd.Grouper(key="date", freq="ME"))["temperature"].mean().dropna()

# =========================================================
# 2. ENTRAÎNEMENT DU MODÈLE SARIMA
# =========================================================
# Meilleur modèle hebdomadaire : SARIMA(1,1,1)(0,1,1,52)
# Meilleur modèle mensuel     : SARIMA(1,1,1)(0,1,1,12)

print("⚙️ Entraînement du modèle avec les hyperparamètres optimaux...")

final_model = SARIMAX(
    monthly,
    order=(1, 1, 1),
    seasonal_order=(0, 1, 1, 12),
    enforce_stationarity=False,
    enforce_invertibility=False
)

final_fit = final_model.fit(disp=False)
print("✅ Modèle entraîné avec succès !")

# =========================================================
# 3. PRÉDICTION (12 mois suivants)
# =========================================================
forecast_obj = final_fit.get_forecast(steps=12)
forecast_mean = forecast_obj.predicted_mean
conf_int = forecast_obj.conf_int()

future_dates = pd.date_range(
    start=monthly.index[-1] + pd.offsets.MonthEnd(1),
    periods=12,
    freq="ME"
)

prevision_df = pd.DataFrame({
    "date":        future_dates,
    "prediction":  forecast_mean.values,
    "borne_inf_95": conf_int.iloc[:, 0].values,
    "borne_sup_95": conf_int.iloc[:, 1].values
})

print(prevision_df.head())
                    </code></pre>
                </div>
            </div>

        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>