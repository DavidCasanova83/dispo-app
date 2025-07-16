MISE À JOUR DE VOS INFORMATIONS D'HÉBERGEMENT
================================================

Bonjour,

Nous avons récemment mis à jour notre base de données d'hébergements touristiques. Votre établissement figure dans notre système et nous souhaitons nous assurer que ses informations sont à jour.

INFORMATIONS DE VOTRE HÉBERGEMENT
=================================

Nom : {{ $accommodation->name }}
ID Apidae : {{ $accommodation->apidae_id }}
@if($accommodation->city)
Ville : {{ $accommodation->city }}
@endif
@if($accommodation->type)
Type : {{ $accommodation->type }}
@endif
Statut actuel : {{ $accommodation->status_label }}

ACTION REQUISE
=============

Nous vous demandons de vérifier et mettre à jour le statut de votre hébergement :

- ACTIF : Si votre établissement est ouvert et prêt à accueillir des clients
- INACTIF : Si votre établissement est temporairement fermé ou non disponible

LIEN DE GESTION
===============

Pour mettre à jour le statut de votre hébergement, cliquez sur ce lien :
{{ $manageUrl }}

IMPORTANT : Ce lien est unique et sécurisé pour votre établissement. Il vous permettra de modifier facilement le statut sans avoir besoin de créer un compte.

Si vous avez des questions ou rencontrez des difficultés, n'hésitez pas à nous contacter.

Cordialement,
L'équipe Dispo App

---
Cet email a été envoyé automatiquement suite à la mise à jour de notre base de données d'hébergements touristiques.
Si vous ne souhaitez plus recevoir ces emails, veuillez nous contacter.