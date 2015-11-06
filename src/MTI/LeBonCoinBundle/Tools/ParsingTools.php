<?php

namespace MTI\LeBonCoinBundle\Tools;

use MTI\UserBackOfficeBundle\Entity\Call;

class ParsingTools
{
    
    public static $regions_map = array(
        "alsace",
        "aquitaine",
        "auvergne",
        "basse_normandie",
        "bourgogne",
        "bretagne",
        "centre",
        "champagne_ardenne",
        "corse",
        "franche_comte",
        "haute_normandie",
        "ile_de_france",
        "languedoc_roussillon",
        "limousin",
        "lorraine",
        "midi_pyrenees",
        "nord_pas_de_calais",
        "pays_de_la_loire",
        "picardie",
        "poitou_charentes",
        "provence_alpes_cote_d_azur",
        "rhone_alpes",
        "guadeloupe",
        "martinique",
        "guyane",
        "reunion"
    );

    public static $regions_match = array(
        "alsace" => "Alsace",
        "aquitaine" => "Aquitaine",
        "auvergne" => "Auvergne",
        "basse_normandie" => "Basse-Normandie",
        "bourgogne" => "Bourgogne",
        "bretagne" => "Bretagne",
        "centre" => "Centre",
        "champagne_ardenne" => "Champagne-Ardenne",
        "corse" => "Corse",
        "franche_comte" => "Franche-Comté",
        "haute_normandie" => "Haute-Normandie",
        "ile_de_france" => "Ile-de-France",
        "languedoc_roussillon" => "Languedoc-Roussillon",
        "limousin" => "Limousin",
        "lorraine" => "Lorraine",
        "midi_pyrenees" => "Midi-Pyrénées",
        "nord_pas_de_calais" => "Nord-Pas-de-Calais",
        "pays_de_la_loire" => "Pays de la Loire",
        "picardie" => "Picardie",
        "poitou_charentes" => "Poitou-Charentes",
        "provence_alpes_cote_d_azur" => "Provence-Alpes-Côte d'Azur",
        "rhone_alpes" => "Rhône-Alpes",
        "guadeloupe" => "Guadeloupe",
        "martinique" => "Martinique",
        "guyane" => "Guyane",
        "reunion" => "Réunion"
    );

    public static $categories_map = array(
        "annonces",
        "offres_d_emploi",
        "voitures",
        "motos",
        "caravaning",
        "utilitaires",
        "equipement_auto",
        "equipement_moto",
        "equipement_caravaning",
        "nautisme",
        "equipement_nautisme",
        "ventes_immobilieres",
        "locations",
        "colocations",
        "bureaux_commerces",
        "locations_gites",
        "chambres_d_hotes",
        "campings",
        "hotels",
        "hebergements_insolites",
        "informatique",
        "consoles_jeux_video",
        "image_son",
        "telephonie",
        "ameublement",
        "electromenager",
        "arts_de_la_table",
        "decoration",
        "linge_de_maison",
        "bricolage",
        "jardinage",
        "vetements",
        "chaussures",
        "accessoires_bagagerie",
        "montres_bijoux",
        "equipement_bebe",
        "dvd_films",
        "cd_musique",
        "livres",
        "animaux",
        "velos",
        "sports_hobbies",
        "instruments_de_musique",
        "collection",
        "jeux_jouets",
        "vins_gastronomie",
        "materiel_agricole",
        "transport_manutention",
        "btp_chantier_gros_oeuvre",
        "outillage_materiaux_2nd_oeuvre",
        "equipements_industriels",
        "restauration_hotellerie",
        "fournitures_de_bureau",
        "commerces_marches",
        "materiel_medical",
        "prestations_de_services",
        "billetterie",
        "evenements",
        "cours_particuliers",
        "covoiturage",
        "autres"
    );

    public static function addRequest($context, $profile, $type, $region, $category) {
        $call = new Call();

        $user = $profile;

        $call->setUserId($user->getId());
        $call->setType($type);
        $call->setRegion($region);
        $call->setCategory($category);

        $em = $context->getDoctrine()->getManager();
        $em->persist($call);
        $em->flush();
    }
}