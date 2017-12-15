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

$string['addbcc'] = 'Añadir a Cco';
$string['addcc'] = 'Añadir a Cc';
$string['addrecipients'] = 'Añade destinatarios';
$string['addto'] = 'Para';
$string['advsearch'] = 'Búsqueda avanzada';
$string['all'] = 'Todos';
$string['applychanges'] = 'Aplica';
$string['assigntonewlabel'] = 'Nueva etiqueta';
$string['attachments'] = 'Adjuntos';
$string['attachnumber'] = '{$a} ficheros adjuntos';
$string['bcc'] = 'Cco';
$string['bulkmessage'] = 'Con los usuarios seleccionados envía un correo interno...';
$string['cancel'] = 'Cancela';
$string['cannotcompose'] = 'No puede redactar mensajes porque no está inscrito en ningún curso.';
$string['cc'] = 'Cc';
$string['compose'] = 'Redacta';
$string['configenablebackup'] = 'Copia de seguridad / restauración';
$string['configenablebackupdesc'] = 'Activa las copias de seguridad y la restauración de mensajes de correo y etiquetas.';
$string['configlegacynav'] = 'Navegación antigua';
$string['configlegacynavdesc'] = 'Usa el estilo antiguo de navegación. No recomendado para Moodle 3.2 o versiones posteriores. Se eliminará en la versión 2.0 de la extensión.';
$string['continue'] = 'Continua';
$string['courses'] = 'Cursos';
$string['delete'] = 'Elimina';
$string['discard'] = 'Descarta';
$string['downloadall'] = 'Descarga todos';
$string['draft'] = 'Borrador';
$string['drafts'] = 'Borradores';
$string['editlabel'] = 'Edita la etiqueta';
$string['emptyrecipients'] = 'No hay destinatarios.';
$string['emptytrash'] = 'Vacía la papelera';
$string['emptytrashconfirm'] = 'Está seguro de vaciar la papelera?';
$string['erroremptycourse'] = 'Por favor indique un curso.';
$string['erroremptylabelname'] = 'Por favor indique un nombre de etiqueta.';
$string['erroremptyrecipients'] = 'Indique un destinatario como mínimo.';
$string['erroremptysubject'] = 'Indique un asunto.';
$string['errorrepeatedlabelname'] = 'El nombre de la etiqueta ya existe';
$string['errorinvalidcolor'] = 'Color no válido';
$string['filterbydate'] = 'Fecha (hasta el día):';
$string['forward'] = 'Reenvia';
$string['from'] = 'De';
$string['hasattachments'] = '(Mensaje con adjuntos)';
$string['inbox'] = 'Bandeja de entrada';
$string['invalidlabel'] = 'Etiqueta no válida';
$string['invalidmessage'] = 'Mensaje no válido';
$string['labeldeleteconfirm'] = 'Está seguro que quiere suprimir la etiqueta \'{$a}\'';
$string['labelname'] = 'Nombre';
$string['labelcolor'] = 'Color';
$string['labels'] = 'Etiquetas';
$string['mail:addinstance'] = 'Añade un nuevo Correo';
$string['mail:mailsamerole'] = 'Envía correos a los usuarios con el mismo rol';
$string['mail:usemail'] = 'Utilizar el Correo';
$string['mailmenu'] = 'Menú del correo';
$string['mailupdater'] = 'Actualización correo';
$string['markasread'] = 'Marca como leído';
$string['markasread_help'] = 'Si se activa, los mensajes recibidos se marcarán como leídos';
$string['markasstarred'] = 'Marca como destacado';
$string['markasunread'] = 'Marca como no leído';
$string['markasunstarred'] = 'Marca como no destacado';
$string['maxattachments'] = 'Número máximo de ficheros adjuntos';
$string['maxattachmentsize'] = 'Tamaño máximo de los ficheros adjuntos';
$string['message'] = 'Mensaje';
$string['messagedeleteconfirm'] = 'Está seguro que quiere suprimir definitivamente el mensaje «{$a}»?';
$string['messagesdeleteconfirm'] = 'Está seguro que quiere suprimir definitivamente {$a} mensaje/s?';
$string['messageprovider:mail'] = 'Notificación de correo';
$string['moreactions'] = 'Más';
$string['mymail'] = 'Mi correo';
$string['newlabel'] = 'Nueva etiqueta';
$string['nocolor'] = 'Sin color';
$string['nolabels'] = 'No hay ninguna etiqueta.';
$string['nomessages'] = 'No hay ningún mensaje.';
$string['nomessageserror'] = 'Para realizar esta acción hay que seleccionar algún mensaje';
$string['nomessagestoview'] = 'No hay mensajes para mostrar.';
$string['none'] = 'Ninguno';
$string['norecipient'] = '(sin destinatarios)';
$string['noselectedmessages'] = 'Ningún mensaje seleccionado';
$string['nosubject'] = '(sin asunto)';
$string['notificationbody'] = '- De: {$a->user}

- Asunto: {$a->subject}

{$a->content}';
$string['notificationbodyhtml'] = '<p>De: {$a->user}</p><p>Asunto: <a href="{$a->url}">{$a->subject}</a></p><p>{$a->content}</p>';
$string['notificationpref'] = 'Envío de notificaciones';
$string['notificationsubject'] = 'Nuevo mensaje de correo en {$a}';
$string['notingroup'] = 'No estáis en ningún grupo';
$string['pagingsingle'] = '{$a->index} de {$a->total}';
$string['pagingmultiple'] = '{$a->first}-{$a->last} de {$a->total}';
$string['perpage'] = 'Muestra {$a} mensajes';
$string['pluginname'] = 'Correo';
$string['read'] = 'Leídos';
$string['references'] = 'Referencias';
$string['removelabel'] = 'Elimina la etiqueta';
$string['reply'] = 'Responde';
$string['replyall'] = 'Responde a todos';
$string['restore'] = 'Restaura';
$string['save'] = 'Guarda';
$string['search'] = 'Busca';
$string['searchbyunread'] = 'Sólo sin leer';
$string['searchbyattach'] = 'Contiene un fichero adjunto';
$string['send'] = 'Envía';
$string['sendmessage'] = 'Envía un mensaje';
$string['sentmail'] = 'Enviados';
$string['setlabels'] = 'Etiquetas';
$string['shortaddbcc'] = 'Cco';
$string['shortaddcc'] = 'Cc';
$string['shortaddto'] = 'Para';
$string['showlabelmessages'] = 'Muestra los mensajes con la etiqueta "{$a}"';
$string['showrecentmessages'] = 'Muestra los mensajes más nuevos';
$string['smallmessage'] = '{$a->user} os ha enviado un mensaje de correo';
$string['starred'] = 'Destacado';
$string['starredmail'] = 'Destacados';
$string['subject'] = 'Asunto';
$string['to'] = 'Para';
$string['togglemailmenu'] = 'Muestra/oculta el menú del correo';
$string['toomanyrecipients'] = 'La búsqueda contiene demasiados resultados';
$string['trash'] = 'Papelera';
$string['undo'] = 'Deshacer';
$string['undodelete'] = 'Se han movido a la papelera {$a} mensajes';
$string['undorestore'] = 'Se han restaurado {$a} mensajes';
$string['unread'] = 'Sin leer';
$string['unstarred'] = 'Sin destacar';
