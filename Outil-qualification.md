# Outil de qualification

Je veux ajouter une grosse nouvelle future dans cette application.
Ajouter une nouvelle fênetre dans le dashboard ⇒ Qualification !

Toutes cette funtionnalité doit être bien séparé dans l’architecture et dans la bdd pour ne pas mélanger les functionnalités et les donnés avec le reste de l’application.

**Dans cette page Qualification je veux :**

Application web de collecte de données touristiques basée sur des formulaires.

**🚀 Fonctionnalités**

-   ✅ Formulaires multi-étapes dynamiques par ville
-   ✅ Interface responsive
-   ✅ Sauvegarde des données en bdd
-   ✅ Persistance des données côté client

**Flux de données**

1. **Sélection de ville** → Génération dynamique des formulaires
2. **Collecte multi-étapes** → Sauvegarde temporaire dans localStorage
3. **Soumission finale** → Envoi vers BDD des informations entrés du formulaire

**📊 Utilisation**

**Formulaires**

1. Accédez à `/{ville}/form1` pour commencer un formulaire
2. Complétez les 3 étapes du formulaire
3. Les données sont sauvegardées automatiquement à chaque étape

**🏙️ Villes disponibles**

Les villes sont définies :

-   Annot
-   Colmars-les-Alpes
-   Entrevaux
-   La Palud-sur-Verdon
-   Saint-André-les-Alpes
