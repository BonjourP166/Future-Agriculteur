# Projet : Calendrier Agricole Intelligent
## Contenu du répertoire GitHub
Ce répertoire contient l’ensemble des éléments nécessaires au projet :
- le rapport complet du projet (avec la description totale du projet et nos démarche) (
- un fichier regroupant les visualisations réalisées lors de l’analyse des données (visualisation/Agriculture_visualisations.ipynb)
- les données brutes ainsi que les données nettoyées utiliser(donnée tableur)
- les fichiers de base de données sql(SQL/agriculture.zip ou SQL/donees)
- un dossier contenant les pages du site web (PHP, HTML, CSS)(Site)
- un dossier contenant des modèles de prédiction développés (modèles)
- les schémas de la base de données (MCD et MOD)(MODMCD)
- le powerpoint de notre présentation(Présentation_agriculture.pdf)
- un script permettant de télécharger et/ou charger l’ensemble des modèles de prédiction(modèles/code_telechargement_model_prediction.ipynb)
L’organisation du dépôt permet de retrouver facilement chaque composant du projet : données, analyses, modèles et applications web.

## Présentation
Ce projet a été réalisé dans le cadre du cours Sciences des données 4 à l’Université Paul-Valéry Montpellier 3 (Département MIASHS).
Il a pour objectif de développer un calendrier agricole intelligent permettant aux agriculteurs, notamment débutants, d’obtenir des recommandations de cultures via un site web.
En entrant un code postal, l’utilisateur peut recevoir des conseils sur les cultures à réaliser en fonction du jour, de la semaine ou du mois. Le site propose également une page de visualisation des données ainsi qu’une page expliquant les modèles de prédiction utilisés.
## Auteurs
Projet réalisé par :
- Margaux Bresson
- Stacy Trocellier
- Florient Marchal
- Ioanna Kortara
## Objectif
Le projet vise à :
- prédire les températures à différentes échelles (journalière, hebdomadaire, mensuelle)
- recommander des cultures adaptées aux conditions climatiques
- proposer un calendrier agricole personnalisé
- rendre les données et modèles compréhensibles via une interface web
## Technologies et outils utilisés
- Python (analyse de données, modèles de prédiction)
- Google Colab
- Google Docs
- LibreOffice
- SQL / MySQL
- MAMP / phpMyAdmin
- PHP, HTML, CSS
- JavaScript (Chart.js)
- Visual Studio Code
- GitHub
## Installation et utilisation
### Prérequis
Pour utiliser ce projet, il est nécessaire de disposer de :
- un environnement local avec MAMP
- phpMyAdmin pour la gestion de la base de données
- un éditeur de code (ex : Visual Studio Code)
- un outil permettant d’ouvrir les fichiers .ipynb (ex : Google Colab)
### Base de données
- Les bases de données sont disponibles dans le dossier dédié. (Donnée tableur)
- Il est nécessaire d’importer la base de données dans phpMyAdmin via MAMP en local (localhost).(SQL/agriculture.zip ou SQL/donees)
- La base de données doit ensuite être reliée au site, notamment aux fichiers contenant les modèles.
- pour l'importation des synop c'est via ce lien:https://www.data.gouv.fr/datasets/archive-synop-omm
### Lancement du site
Le site peut être :
- exécuté en local (localhost) via MAMP (il devra être relié à la base sql pour fonctionner)
- accessible via un lien :https://www.martchal.fr/planning_agricole/index.php
## Structure du projet
### Site web
(dans le dossier Site)


- algo.php
- api_prediction.py
- bd.php
- calendrier_agricole.php
- events_agricoles.php
- footer.php
- get_station_proche.php
- index.php
- nav.php
- prediction_complete.php
- prediction_cultures.php
- requirements.txt
- synop_2022.csv
- synop_2023.csv
- synop_2024.csv
- synop_2025.csv
- synop_2026.csv
- test.php
- train_lstm.py
- train_sarima.py
- visu_base.php
- visu_stat.php

modele_lstm.keras:
- BORDEAUX-MERIGNAC_lstm_24h.keras
- BREST-GUIPAVAS_lstm_24h.keras
- LILLE-LESQUIN_lstm_24h.keras
- LYON-ST_EXUPERY_lstm_24h.keras
- MONTPELLIER-AEROPORT_lstm_24h.keras
- NANTES-BOUGUENAIS_lstm_24h.keras
- NICE_lstm_24h.keras
- PERPIGNAN_lstm_24h.keras
- STRASBOURG-ENTZHEIM_lstm_24h.keras
- TOULOUSE-BLAGNAC_lstm_24h.keras

models_sarima_monthly:
- .venv: (cest un dossier tecnique)

- models_sarima_monthly:
- - sarima_monthly_BORDEAUX_MERIGNAC.pkl
- - sarima_monthly_BREST_GUIPAVAS.pkl
- - sarima_monthly_LILLE_LESQUIN.pkl
- - sarima_monthly_LYON_ST_EXUPERY.pkl
- -  sarima_monthly_MONTPELLIER_AEROPORT.pkl
- - sarima_monthly_NANTES_BOUGUENAIS.pkl
- - sarima_monthly_NICE.pkl
- - sarima_monthly_PERPIGNAN.pkl
- - sarima_monthly_STRASBOURG_ENTZHEIM.pkl
- - sarima_monthly_TOULOUSE_BLAGNAC.pkl
 


- sarima_monthly_BORDEAUX_MERIGNAC.pkl
- sarima_monthly_BREST_GUIPAVAS.pkl
- sarima_monthly_LILLE_LESQUIN.pkl
- sarima_monthly_LYON_ST_EXUPERY.pkl
- sarima_monthly_MONTPELLIER_AEROPORT.pkl
- sarima_monthly_NANTES_BOUGUENAIS.pkl
- sarima_monthly_NICE.pkl
- sarima_monthly_PERPIGNAN.pkl
- sarima_monthly_STRASBOURG_ENTZHEIM.pkl
- sarima_monthly_TOULOUSE_BLAGNAC.pkl


models_sarima_weekly:
- sarima_weekly_BORDEAUX_MERIGNAC.pkl
- sarima_weekly_BREST_GUIPAVAS.pkl
- sarima_weekly_LILLE_LESQUIN.pkl
- sarima_weekly_LYON_ST_EXUPERY.pkl
- sarima_weekly_MONTPELLIER_AEROPORT.pkl
- sarima_weekly_NANTES_BOUGUENAIS.pkl
- sarima_weekly_NICE.pkl
- sarima_weekly_PERPIGNAN.pkl
- sarima_weekly_STRASBOURG_ENTZHEIM.pkl
- sarima_weekly_TOULOUSE_BLAGNAC.pkl

normales_map_lstm.pkl:
- BORDEAUX-MERIGNAC_normales_map.pkl
- BREST-GUIPAVAS_normales_map.pkl
- LILLE-LESQUIN_normales_map.pkl
- LYON-ST_EXUPERY_normales_map.pkl
- MONTPELLIER-AEROPORT_normales_map.pkl
- NANTES-BOUGUENAIS_normales_map.pkl
- NICE_normales_map.pkl
- PERPIGNAN_normales_map.pkl
- STRASBOURG-ENTZHEIM_normales_map.pkl
- TOULOUSE-BLAGNAC_normales_map.pkl

scaler_x_lstm.pkl:
- BORDEAUX-MERIGNAC_scaler_X.pkl
- BREST-GUIPAVAS_scaler_X.pkl
- LILLE-LESQUIN_scaler_X.pkl
- LYON-ST_EXUPERY_scaler_X.pkl
- MONTPELLIER-AEROPORT_scaler_X.pkl
- NANTES-BOUGUENAIS_scaler_X.pkl
- NICE_scaler_X.pkl
- PERPIGNAN_scaler_X.pkl
- STRASBOURG-ENTZHEIM_scaler_X.pkl
- TOULOUSE-BLAGNAC_scaler_X.pkl

scaler_y_lstm.pkl:
- BORDEAUX-MERIGNAC_scaler_y.pkl
- BREST-GUIPAVAS_scaler_y.pkl
- LILLE-LESQUIN_scaler_y.pkl
- LYON-ST_EXUPERY_scaler_y.pkl
- MONTPELLIER-AEROPORT_scaler_y.pkl
- NANTES-BOUGUENAIS_scaler_y.pkl
- NICE_scaler_y.pkl
- PERPIGNAN_scaler_y.pkl
- STRASBOURG-ENTZHEIM_scaler_y.pkl
- TOULOUSE-BLAGNAC_scaler_y.pkl

styles:
- algo.css
- calendrier.css
- connexion.css
- footer.css
- index.css
- mon_compte.css
- nav.css
- styles.css
- visu.css
- visu_2.css

### Modèles
Modèles testés :
- Régression linéaire
- Random Forest
- ARIMA / SARIMA / SARIMAX
- Prophet
- XGBoost
- LSTM
## Données
Le projet repose sur trois types de données :
- données météorologiques (stations françaises, relevés temporels)
- données agricoles (cultures, températures, saisonnalité,sols)
- données géographiques (villes, codes postaux)
## Fonctionnalités
### Site web
- saisie du code postal
- recommandations de cultures
- calendrier de plantation
- visualisation des données
- explication des modèles utilisés
### Analyse
- exploration statistique des données
- visualisations des tendances climatiques
- étude des relations entre variables


## Limites
- volume important de données
- contraintes de performance (stockage et calcul)
- difficulté d’utilisation de certaines variables en prédiction réelle
## Perspectives
- amélioration des performances des modèles
- ajout de nouvelles variables (sol, irrigation)
- extension à d’autres zones géographiques
- amélioration de l’interface utilisateur
## Licence
Projet académique réalisé dans un cadre universitaire.


