import warnings
warnings.filterwarnings("ignore")

import pandas as pd
import glob
import pickle
import os
import re

from statsmodels.tsa.statespace.sarimax import SARIMAX

# =========================================================
# CONFIG
# =========================================================
MODEL_DIR = "models_sarima_weekly"
os.makedirs(MODEL_DIR, exist_ok=True)

# Mieux que 10 pour un SARIMA hebdo saisonnier 52
MIN_DATA_POINTS = 60

STATIONS_A_GARDER = [
    "MONTPELLIER-AEROPORT",
    "TOULOUSE-BLAGNAC",
    "BORDEAUX-MERIGNAC",
    "LYON-ST EXUPERY",
    "NANTES-BOUGUENAIS",
    "STRASBOURG-ENTZHEIM",
    "LILLE-LESQUIN",
    "BREST-GUIPAVAS",
    "NICE",
    "PERPIGNAN"
]

# =========================================================
# OUTIL : nom de fichier propre
# =========================================================
def safe_filename(name: str) -> str:
    name = name.strip().upper()
    name = name.replace(" ", "_").replace("-", "_").replace("'", "_")
    name = re.sub(r"[^A-Z0-9_]", "_", name)
    name = re.sub(r"_+", "_", name)
    return name.strip("_")

# =========================================================
# 1. CHARGEMENT CSV
# =========================================================
print("⏳ Chargement des fichiers SYNOP...")

files = sorted(glob.glob("synop_*.csv"))

if not files:
    raise FileNotFoundError("Aucun fichier synop_*.csv trouvé.")

cols = ["validity_time", "t", "name"]

df_raw = pd.concat(
    [pd.read_csv(f, sep=None, engine="python", usecols=cols) for f in files],
    ignore_index=True
)

df_raw["date"] = pd.to_datetime(df_raw["validity_time"], utc=True, errors="coerce")
df_raw["temperature"] = pd.to_numeric(df_raw["t"], errors="coerce") - 273.15

df_raw = df_raw.dropna(subset=["date", "temperature", "name"]).sort_values("date")

# On garde seulement les 10 stations voulues
df_raw = df_raw[df_raw["name"].isin(STATIONS_A_GARDER)].copy()

print(f"✅ Données filtrées : {len(df_raw)} lignes")
print(f"📊 Stations retenues : {df_raw['name'].nunique()}")

# Vérification des stations trouvées / absentes
stations_presentes = sorted(df_raw["name"].unique())
stations_absentes = [s for s in STATIONS_A_GARDER if s not in stations_presentes]

print("✅ Stations présentes :", stations_presentes)
if stations_absentes:
    print("⚠️ Stations absentes des CSV :", stations_absentes)

# =========================================================
# 2. ENTRAÎNEMENT PAR STATION
# =========================================================
success = 0
skipped = 0

for station in STATIONS_A_GARDER:
    print(f"\n🔄 Station : {station}")

    df = df_raw[df_raw["name"] == station].copy()

    if df.empty:
        print("⚠️ Aucune donnée pour cette station")
        skipped += 1
        continue

    # Agrégation hebdomadaire
    weekly = df.groupby(pd.Grouper(key="date", freq="W-MON"))["temperature"].mean()

    # On fixe explicitement la fréquence
    weekly = weekly.asfreq("W-MON")

    # On enlève les NaN pour le test quantité
    weekly_non_na = weekly.dropna()

    print(f"📈 Nombre de semaines exploitables : {len(weekly_non_na)}")

    if len(weekly_non_na) < MIN_DATA_POINTS:
        print("⚠️ Pas assez de données hebdomadaires")
        skipped += 1
        continue

    try:
        model = SARIMAX(
            weekly,
            order=(1, 1, 1),
            seasonal_order=(0, 1, 1, 52),
            enforce_stationarity=False,
            enforce_invertibility=False
        )

        fit = model.fit(disp=False)

        station_safe = safe_filename(station)
        model_path = os.path.join(MODEL_DIR, f"sarima_weekly_{station_safe}.pkl")

        with open(model_path, "wb") as f:
            pickle.dump(fit, f)

        print(f"✅ Modèle sauvegardé : {model_path}")
        success += 1

    except Exception as e:
        print(f"❌ Erreur : {e}")
        skipped += 1

# =========================================================
# FIN
# =========================================================
print("\n🎯 TERMINÉ")
print(f"✅ Succès : {success}")
print(f"⚠️ Ignorées : {skipped}")