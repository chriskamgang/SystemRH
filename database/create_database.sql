-- ============================================
-- Création de la base de données
-- Application de pointage par géolocalisation
-- ============================================

-- Supprimer la base de données si elle existe déjà (ATTENTION : ceci supprime toutes les données)
-- DROP DATABASE IF EXISTS attendance_system;

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS attendance_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Sélectionner la base de données
USE attendance_system;

-- Vérification
SELECT 'Base de données "attendance_system" créée avec succès !' AS message;
