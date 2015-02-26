<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'report_learningtimecheck'.
 *
 * @package    report
 * @subpackage learningtimecheck
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Temps d\'apprentissage';
$string['learningtimecheck:view'] = 'Voir les rapports de temps';
$string['learningtimecheck:viewother'] = 'Voir les rapports de temps d\'autres utilisateurs';
$string['learningtimecheck:export'] = 'Exporter des données de marquage';

$string['page-report-learningtimecheck-index'] = 'Sommaire des rapports de temps';
$string['page-report-learningtimecheck-x'] = 'Rapport de temps';

$string['allusers'] = 'Tous les utilisateurs';
$string['activeusers'] = 'Utilisateurs actifs';
$string['nullusers'] = 'Utilisateurs à 0%';
$string['midrangeusers'] = 'Utilisateurs à mi parcours';
$string['fullusers'] = 'Utilisateurs > 90%';
$string['addbatch'] = 'Ajouter un batch';
$string['backtoindex'] = 'Retour à la sélection';
$string['batchs'] = 'Batchs';
$string['batchname'] = 'Nom de la tâche';
$string['changeoptions'] = 'Changer mes options';
$string['clearall'] = 'Supprimer les batchs partagés';
$string['clearmarks'] = 'Supprimer les marques';
$string['clearowned'] = 'Supprimer mes batchs';
$string['cohort'] = 'Cohorte';
$string['cohortreport'] = 'Cohorte : {$a->name} [{$a->idnumber}]';
$string['course'] = 'Cours';
$string['coursereport'] = 'Cours : {$a->shortname} {$a->fullname} [{$a->idnumber}]';
$string['detail'] = 'Détail';
$string['repeat'] = 'Répétition (mn)';
$string['torun'] = 'Imminent';
$string['disabled'] = 'Inactif';
$string['doneratio'] = '% temps effectué';
$string['exportpdf'] = 'Exporter en PDF';
$string['exportpdfdetail'] = 'Exporter le détail en PDF';
$string['exportxls'] = 'Exporter en Excel';
$string['exportxlsdetail'] = 'Exporter le détail en Excel';
$string['exportxml'] = 'Exporter en XML';
$string['exportxmldetail'] = 'Exporter le détail en XML';
$string['exportcsv'] = 'Exporter en CSV';
$string['exportcsvdetail'] = 'Exporter le détail en CSV';
$string['fromnow'] = 'Dans {$a} à partir de maintenant';
$string['filters'] = 'Filtres';
$string['globalbatchs'] = 'Batchs partagés';
$string['groupseparation'] = 'Mode de séparation';
$string['groupseparation_desc'] = 'Choisit si les rapports sont dissociés par groupes ou par groupement.';
$string['idnumber'] = 'ID';
$string['invalidgroupaccess'] = 'Accès non autorisé pour ce groupe';
$string['item'] = 'Item';
$string['detail'] = 'Batch de détail';
$string['itemnamepdf'] = 'Elément de marquage';
$string['itemtimecreditpdf'] = 'Crédit temps';
$string['leftratio'] = 'Reste à faire (%)';
$string['makebatch'] = 'Créer des batchs à partir des marques';
$string['myreportoptions'] = 'Mes options';
$string['nobatchs'] = 'Aucun batch enregistré';
$string['noresults'] = 'Aucun résultat';
$string['noresults'] = 'Pas de résultats';
$string['notifyemails'] = 'Emails à notifier';
$string['nousers'] = 'Aucun utilisateur.';
$string['output'] = 'Sortie';
$string['ownedbatchs'] = 'Mes batchs';
$string['params'] = 'Paramètres de batch';
$string['pdfpage'] = 'Page : {$a}';
$string['pdfreportfooter'] = 'Image pour pied de page PDF';
$string['pdfreportfooter_desc'] = 'Permet de fournir une image à utiliser comme pied de page du document PDF (880px de large x jusqu\'à 100px de haut)';
$string['pdfreportheader'] = 'Image pour en-tête PDF';
$string['pdfreportheader_desc'] = 'Permet de fournir une image à utiliser comme en-tête du document PDF pour la première page (880px de large x jusqu\'à 220px de haut)';
$string['pdfreportinnerheader'] = 'Image pour en-tête interne PDF';
$string['pdfreportinnerheader_desc'] = 'Permet de fournir une image à utiliser comme en-tête du document PDF pour les pages suivantes (880px de large x jusqu\'à 150px de haut). Si aucune n\'est fournie, l\'image de première en-tête est utilisée par défaut pour toutes les pages.';
$string['pendings'] = 'Programmation';
$string['pruneprocessedbatchsafter'] = 'Supprimer les batchs terminés après';
$string['pruneprocessedbatchsafter_desc'] = 'Une fois terminés, les batchs restent dans le registre pendant un certain temps pour tracer explicitement l\'exécution';
$string['newbatch'] = '< Nouveau rapport programmé >';
$string['repeatdelay'] = 'Délai pour répétition';
$string['recipient'] = 'Destinataire';
$string['recipient_desc'] = 'Destinataire par défaut des documents PDF. Peut être surchargé localement par l\'utilisateur.';
$string['reportdate'] = 'Date du rapport';
$string['reportforcohort'] = 'Rapport de la cohorte';
$string['reportforcourse'] = 'Rapport du cours';
$string['reportforuser'] = 'Rapport de l\'étudiant';
$string['results'] = 'Résultats';
$string['schedule'] = 'Enregistrer';
$string['scheduleabatch'] = 'Planifier un batch';
$string['sendtobatch'] = 'Planifier';
$string['senddetailtobatch'] = 'Planifier la sortie de détail';
$string['sharebatch'] = 'Partager ce batch';
$string['searchbytext'] = 'Recherche plein texte';
$string['searchcourses'] = 'Chercher un cours';
$string['searchincategories'] = 'Recherche par catégorie';
$string['runtime'] = 'Heure';
$string['top'] = '(Niveau supérieur)';
$string['type'] = 'Type de rapport';
$string['updatetype'] = '(caché)';
$string['user'] = 'Utilisateur';
$string['userreport'] = 'Etudiant : {$a->firstname} {$a->lastname} [{$a->idnumber}]';
$string['vacationdays'] = 'Jours chômés';
$string['vacationdays_help'] = 'Donner le liste des induces de jours (jour dans l\'année) des jours chômés à ne pas prendre en compte';
$string['weekrangeend'] = 'Dernière semaine de la période';
$string['weekrangestart'] = 'Première semaine de la période';
$string['workendtime'] = 'Fin heures ouvrables';
$string['workstarttime'] = 'Début heures ouvrables';
$string['validatedbypdf'] = 'Validé par';
$string['selfmarked'] = 'Auto';
$string['usercourseprogress'] = 'Indice de progression ';
$string['usertimeearned'] = 'temps acquis ';
$string['worktimefilter'] = 'Horaires hebdomadaires ';
$string['weekrangefilter'] = 'Intervalle de semaines (bornes incluses)';
$string['weekrangestart'] = 'Semaine de départ';
$string['weekrangeend'] = 'Semaine de fin';
$string['pdfoptions'] = 'Options pdf';
$string['pdfshowidnumbers'] = 'Afficher les numéros d\'identification';
$string['pdfshowgroups'] = 'Afficher les groupes';

$string['idnumberpdf'] = 'ID';
$string['progressbarpdf'] = '% effectué';
$string['itemstodopdf'] = 'Requis';
$string['doneitemspdf'] = 'Effectué';
$string['timedonepdf'] = 'Passé';
$string['ratioleftpdf'] = '% reste';
$string['doneratiopdf'] = '% effectué';
$string['timeleftpdf'] = 'Reste';
$string['itemspdf'] = 'Eléments';
$string['timepdf'] = 'Temps';

$string['errornoexporterclass'] = 'Classe d\'exportation {$a} manquante';
