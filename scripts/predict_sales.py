#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de Prédiction des Ventes avec Intelligence Artificielle
Utilise LinearRegression de Scikit-Learn pour prédire le CA du mois prochain
"""

import sys
import json
import mysql.connector
from mysql.connector import Error
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
from datetime import datetime, timedelta
import warnings

warnings.filterwarnings('ignore')


def connect_to_database(host, database, user, password):
    """
    Connexion sécurisée à la base de données MySQL
    """
    try:
        connection = mysql.connector.connect(
            host=host,
            database=database,
            user=user,
            password=password,
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        
        if connection.is_connected():
            return connection
        else:
            return None
            
    except Error as e:
        print(json.dumps({
            "success": False,
            "error": f"Erreur de connexion à la base de données: {str(e)}",
            "prediction": None
        }), file=sys.stderr)
        return None


def fetch_sales_data(connection):
    """
    Récupère les ventes groupées par mois depuis la base de données
    """
    try:
        cursor = connection.cursor(dictionary=True)
        
        query = """
            SELECT 
                DATE_FORMAT(date_vente, '%Y-%m') as mois,
                SUM(montant_total) as total_ventes,
                COUNT(*) as nombre_ventes
            FROM vente
            WHERE date_vente IS NOT NULL
            GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
            ORDER BY mois ASC
        """
        
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        
        if not results or len(results) < 2:
            return None, "Pas assez de données historiques (minimum 2 mois requis)"
        
        return results, None
        
    except Error as e:
        return None, f"Erreur lors de la récupération des données: {str(e)}"


def prepare_data(sales_data):
    """
    Prépare les données pour l'entraînement du modèle
    """
    # Convertir en DataFrame pandas
    df = pd.DataFrame(sales_data)
    
    # Convertir les montants en float
    df['total_ventes'] = df['total_ventes'].astype(float)
    df['nombre_ventes'] = df['nombre_ventes'].astype(int)
    
    # Créer une colonne numérique pour les mois (0, 1, 2, ...)
    df['mois_num'] = range(len(df))
    
    return df


def train_model(df):
    """
    Entraîne le modèle de régression linéaire
    """
    # Préparer X (mois) et y (montant des ventes)
    X = df[['mois_num']].values
    y = df['total_ventes'].values
    
    # Créer et entraîner le modèle
    model = LinearRegression()
    model.fit(X, y)
    
    return model


def predict_next_month(model, df):
    """
    Prédit le chiffre d'affaires du mois prochain
    """
    # Le prochain mois est le numéro suivant
    next_month_num = len(df)
    
    # Faire la prédiction
    prediction = model.predict([[next_month_num]])[0]
    
    # S'assurer que la prédiction est positive
    prediction = max(0, prediction)
    
    return prediction


def calculate_statistics(df, prediction):
    """
    Calcule des statistiques supplémentaires
    """
    stats = {
        'moyenne_mensuelle': float(df['total_ventes'].mean()),
        'dernier_mois': float(df['total_ventes'].iloc[-1]),
        'mois_precedent': float(df['total_ventes'].iloc[-2]) if len(df) >= 2 else None,
        'tendance': 'hausse' if prediction > df['total_ventes'].iloc[-1] else 'baisse',
        'variation_pourcent': round(((prediction - df['total_ventes'].iloc[-1]) / df['total_ventes'].iloc[-1]) * 100, 2) if df['total_ventes'].iloc[-1] > 0 else 0,
        'nombre_mois_historique': len(df),
        'total_historique': float(df['total_ventes'].sum())
    }
    
    return stats


def main():
    """
    Fonction principale
    """
    # Vérifier les arguments
    if len(sys.argv) != 5:
        print(json.dumps({
            "success": False,
            "error": "Arguments manquants. Usage: python predict_sales.py <host> <database> <user> <password>",
            "prediction": None
        }))
        sys.exit(1)
    
    host = sys.argv[1]
    database = sys.argv[2]
    user = sys.argv[3]
    password = sys.argv[4]
    
    # Connexion à la base de données
    connection = connect_to_database(host, database, user, password)
    
    if connection is None:
        print(json.dumps({
            "success": False,
            "error": "Impossible de se connecter à la base de données",
            "prediction": None
        }))
        sys.exit(1)
    
    try:
        # Récupérer les données
        sales_data, error = fetch_sales_data(connection)
        
        if error:
            print(json.dumps({
                "success": False,
                "error": error,
                "prediction": None
            }))
            sys.exit(1)
        
        # Préparer les données
        df = prepare_data(sales_data)
        
        # Entraîner le modèle
        model = train_model(df)
        
        # Faire la prédiction
        prediction = predict_next_month(model, df)
        
        # Calculer les statistiques
        stats = calculate_statistics(df, prediction)
        
        # Calculer le mois prédit
        last_month = df['mois'].iloc[-1]
        last_date = datetime.strptime(last_month + '-01', '%Y-%m-%d')
        next_month_date = last_date + timedelta(days=32)
        next_month_date = next_month_date.replace(day=1)
        predicted_month = next_month_date.strftime('%Y-%m')
        
        # Retourner le résultat en JSON
        result = {
            "success": True,
            "prediction": round(prediction, 2),
            "predicted_month": predicted_month,
            "statistics": stats,
            "error": None
        }
        
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": f"Erreur inattendue: {str(e)}",
            "prediction": None
        }))
        sys.exit(1)
        
    finally:
        if connection and connection.is_connected():
            connection.close()


if __name__ == "__main__":
    main()
