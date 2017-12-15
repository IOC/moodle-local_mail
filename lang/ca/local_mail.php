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
 * @package    local_mail
 * @author     Albert Gasset <albert.gasset@gmail.com>
 * @author     Marc Català <reskit@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addbcc'] = 'Afegeix a c/o';
$string['addcc'] = 'Afegeix a a/c';
$string['addrecipients'] = 'Afegeix destinataris';
$string['addto'] = 'Per a';
$string['advsearch'] = 'Cerca avançada';
$string['all'] = 'Tots';
$string['applychanges'] = 'Aplica';
$string['assigntonewlabel'] = 'Etiqueta nova';
$string['attachments'] = 'Fitxers adjunts';
$string['attachnumber'] = '{$a} fitxers adjunts';
$string['bcc'] = 'C/o';
$string['bulkmessage'] = 'Amb els usuaris seleccionats envia un correu intern...';
$string['cancel'] = 'Cancel·la';
$string['cannotcompose'] = 'No podeu redactar missatges perquè no esteu inscrit a cap curs.';
$string['cc'] = 'A/c';
$string['compose'] = 'Redacta';
$string['configenablebackup'] = 'Còpia de seguretat / restauració';
$string['configenablebackupdesc'] = 'Habilita les còpies de seguretat i la restauració de missatges de correu i etiquetes.';
$string['configlegacynav'] = 'Navegació antiga';
$string['configlegacynavdesc'] = 'Fer servir l\'estil antic de navegació. No recomanat per a Moodle 3.2 o versions posteriors. S\'eliminarà a la versió 2.0 del connector.';
$string['continue'] = 'Continua';
$string['courses'] = 'Cursos';
$string['delete'] = 'Suprimeix';
$string['discard'] = 'Descarta';
$string['downloadall'] = 'Baixa\'ls tots';
$string['draft'] = 'Esborrany';
$string['drafts'] = 'Esborranys';
$string['editlabel'] = 'Edita l\'etiqueta';
$string['emptyrecipients'] = 'No hi ha destinataris.';
$string['emptytrash'] = 'Buida la paperera';
$string['emptytrashconfirm'] = 'Esteu segur de buidar la paperera?';
$string['erroremptycourse'] = 'Indiqueu un curs.';
$string['erroremptylabelname'] = 'Indiqueu un nom d\'etiqueta.';
$string['erroremptyrecipients'] = 'Indiqueu un destinatari com a mínim.';
$string['erroremptysubject'] = 'Indiqueu l\'assumpte.';
$string['errorinvalidcolor'] = 'Color no vàlid';
$string['errorrepeatedlabelname'] = 'El nom d\'etiqueta ja existeix';
$string['filterbydate'] = 'Data (fins al dia):';
$string['forward'] = 'Reenvia';
$string['from'] = 'De';
$string['hasattachments'] = '(Missatge amb fitxers adjunts)';
$string['inbox'] = 'Safata d\'entrada';
$string['invalidlabel'] = 'Etiqueta no vàlida';
$string['invalidmessage'] = 'Missatge no vàlid';
$string['labelcolor'] = 'Color';
$string['labeldeleteconfirm'] = 'Esteu segur que voleu suprimir l\'etiqueta «{$a}»';
$string['labelname'] = 'Nom';
$string['labels'] = 'Etiquetes';
$string['mail:addinstance'] = 'Afegeix un correu nou';
$string['mail:mailsamerole'] = 'Envia correus als usuaris amb el mateix rol';
$string['mail:usemail'] = 'Utilitza el correu';
$string['mailmenu'] = 'Menú del correu';
$string['mailupdater'] = 'Actualització de correu';
$string['markasread'] = 'Marca com a llegit';
$string['markasread_help'] = 'Si està activat, els missatges rebuts es marcaran com a llegits';
$string['markasstarred'] = 'Marca com a destacat';
$string['markasunread'] = 'Marca com a no llegit';
$string['markasunstarred'] = 'Marca com a no destacat';
$string['maxattachments'] = 'Nombre màxim de fitxers adjunts';
$string['maxattachmentsize'] = 'Mida màxima dels fitxers adjunts';
$string['message'] = 'Missatge';
$string['messagedeleteconfirm'] = 'Esteu segur que voleu suprimir definitivament el missatge «{$a}»?';
$string['messagesdeleteconfirm'] = 'Esteu segur que voleu suprimir definitivament {$a} missatge/s?';
$string['messageprovider:mail'] = 'Notificació de correu';
$string['moreactions'] = 'Més';
$string['mymail'] = 'El meu correu';
$string['newlabel'] = 'Etiqueta nova';
$string['nocolor'] = 'Sense color';
$string['nolabels'] = 'No hi ha cap etiqueta.';
$string['nomessages'] = 'No hi ha cap missatge.';
$string['nomessageserror'] = 'Per realitzar aquesta acció cal seleccionar algun missatge';
$string['nomessagestoview'] = 'No hi ha missatges per mostrar.';
$string['none'] = 'Cap';
$string['norecipient'] = '(sense destinataris)';
$string['noselectedmessages'] = 'Cap missatge seleccionat';
$string['nosubject'] = '(sense assumpte)';
$string['notificationbody'] = '- De: {$a->user}

- Assumpte: {$a->subject}

{$a->content}';
$string['notificationbodyhtml'] = '<p>De: {$a->user}</p><p>Assumpte: <a href="{$a->url}">{$a->subject}</a></p><p>{$a->content}</p>';
$string['notificationpref'] = 'Notificacions d\'enviament';
$string['notificationsubject'] = 'Missatge de correu electrònic nou a {$a}';
$string['notingroup'] = 'No esteu a cap grup';
$string['pagingmultiple'] = '{$a->first}-{$a->last} de {$a->total}';
$string['pagingsingle'] = '{$a->index} de {$a->total}';
$string['perpage'] = 'Mostra {$a} missatges';
$string['pluginname'] = 'Correu';
$string['read'] = 'Llegits';
$string['references'] = 'Referències';
$string['removelabel'] = 'Elimina l\'etiqueta';
$string['reply'] = 'Respon';
$string['replyall'] = 'Respon a tothom';
$string['restore'] = 'Restaura';
$string['save'] = 'Desa';
$string['search'] = 'Cerca';
$string['searchbyattach'] = 'Conté un fitxer adjunt';
$string['searchbyunread'] = 'Només sense llegir';
$string['send'] = 'Envia';
$string['sendmessage'] = 'Envia un missatge';
$string['sentmail'] = 'Enviats';
$string['setlabels'] = 'Etiquetes';
$string['shortaddbcc'] = 'C/o';
$string['shortaddcc'] = 'A/c';
$string['shortaddto'] = 'Per a';
$string['showlabelmessages'] = 'Mostra els missatges amb l\'etiqueta «{$a}»';
$string['showrecentmessages'] = 'Mostra els missatges més nous';
$string['smallmessage'] = '{$a->user} us ha enviat un missatge de correu';
$string['starred'] = 'Destacat';
$string['starredmail'] = 'Destacats';
$string['subject'] = 'Assumpte';
$string['to'] = 'Per a';
$string['togglemailmenu'] = 'Mostra/amaga el menú del correu';
$string['toomanyrecipients'] = 'La cerca conté massa resultats';
$string['trash'] = 'Paperera';
$string['undo'] = 'Desfés';
$string['undodelete'] = 'S\'han mogut a la paperera {$a} missatges';
$string['undorestore'] = 'S\'han restaurat {$a} missatges';
$string['unread'] = 'Sense llegir';
$string['unstarred'] = 'Sense destacar';
