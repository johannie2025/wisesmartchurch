<?php
// À adapter à votre hébergement.
const DB_HOST = 'localhost';
const DB_NAME = 'wdscs';
const DB_USER = 'wdscs';
const DB_PASS = 'CHANGEZ-MOI';
const CURRENCY = '€';
// Clé de chiffrement des clés API stockées en base (changez-la, 32+ caractères aléatoires) :
const AI_KEY_SECRET = 'CHANGEZ-MOI-PAR-UNE-LONGUE-CHAINE-ALEATOIRE';
// Dossier des installeurs (hors web si possible ; protégé par .htaccess sinon)
const FILES_DIR = __DIR__ . '/files';
