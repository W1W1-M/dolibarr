<?php
/* Copyright (C) 2013-2014  Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/resource/list.php
 *      \ingroup    resource
 *      \brief      Page to manage resource objects
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

// Load translation files required by the page
$langs->loadLangs(array("resource", "companies", "other"));

// Get parameters
$id             = GETPOSTINT('id');
$action         = GETPOST('action', 'alpha');
$massaction     = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)

$lineid         = GETPOSTINT('lineid');
$element        = GETPOST('element', 'alpha');
$element_id     = GETPOSTINT('element_id');
$resource_id    = GETPOSTINT('resource_id');

$sortorder      = GETPOST('sortorder', 'aZ09comma');
$sortfield      = GETPOST('sortfield', 'aZ09comma');
$optioncss = GETPOST('optioncss', 'alpha');

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'resourcelist';

// Initialize technical objects
$object = new Dolresource($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
if (!is_array($search_array_options)) {
	$search_array_options = array();
}
$search_ref = GETPOST("search_ref", 'alpha');
$search_type = GETPOST("search_type", 'alpha');
$search_address = GETPOST('search_address', 'alpha');
$search_zip = GETPOST("search_zip", 'alpha');
$search_town = GETPOST("search_town", 'alpha');
$search_max_users = GETPOST("search_max_users", 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;

$filter = array();

$hookmanager->initHooks(array('resourcelist'));

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "t.ref";
}
if (empty($arch)) {
	$arch = 0;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$arrayfields = array(
	't.ref' => array(
		'label' => $langs->trans("Ref"),
		'position' => 1,
		'checked' => 1
	),
	'ty.label' => array(
		'label' => $langs->trans("ResourceType"),
		'position' => 2,
		'checked' => 1
	),
	't.address' => array(
		'label' => $langs->trans("Address"),
		'position' => 3,
		'checked' => 0
	),
	't.zip' => array(
		'label' => $langs->trans("Zip"),
		'position' => 4,
		'checked' => 0
	),
	't.town' => array(
		'label' => $langs->trans("Town"),
		'position' => 5,
		'checked' => 1
	),
	't.max_users' => array(
		'label' => $langs->trans("Max_users"),
		'position' => 6,
		'checked' => 1
	),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_ref = "";
	$search_type = "";
	$search_address = "";
	$search_zip = "";
	$search_town = "";
	$search_max_users = "";
	$search_array_options = array();
	$filter = array();
}

if (!$user->hasRight('resource', 'read')) {
	accessforbidden();
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * View
 */

$form = new Form($db);

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';
$title = $langs->trans('Resources');
llxHeader('', $title, $help_url);


$sql = '';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}

if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
	$filter['t.ref'] = $search_ref;
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
	$filter['ty.label'] = $search_type;
}
if ($search_address != '') {
	$param .= '&search_address='.urlencode($search_address);
	$filter['t.address'] = $search_address;
}
if ($search_zip != '') {
	$param .= '&search_zip='.urlencode($search_zip);
	$filter['t.zip'] = $search_zip;
}
if ($search_town != '') {
	$param .= '&search_town='.urlencode($search_town);
	$filter['t.town'] = $search_town;
}
if ($search_max_users != '') {
	$param .= '&search_max_users='.urlencode($search_max_users);
	$filter['t.max_users'] = $search_max_users;
}
// Including the previous script generate the correct SQL filter for all the extrafields
// we are playing with the behaviour of the Dolresource::fetchAll() by generating a fake
// extrafields filter key to make it works
$filter['ef.resource'] = $sql;

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


// Confirmation suppression resource line
if ($action == 'delete_resource') {
	print $form->formconfirm($_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id."&lineid=".$lineid, $langs->trans("DeleteResource"), $langs->trans("ConfirmDeleteResourceElement"), "confirm_delete_resource", '', '', 1);
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$ret = $object->fetchAll('', '', 0, 0, $filter);
	if ($ret == -1) {
		dol_print_error($db, $object->error);
		exit;
	} else {
		$nbtotalofrecords = $ret;
	}
}

// Load object list
$ret = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
if ($ret == -1) {
	dol_print_error($db, $object->error);
	exit;
} else {
	$newcardbutton = '';
	if ($user->hasRight('resource', 'write')) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('MenuResourceAdd'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/resource/card.php?action=create');
	}

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $ret + 1, $nbtotalofrecords, 'object_resource', 0, $newcardbutton, '', $limit, 0, 0, 1);
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
if (!empty($arrayfields['ty.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_type" value="'.dol_escape_htmltag($search_type).'">';
	print '</td>';
}
if (!empty($arrayfields['t.address']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_address" value="'.dol_escape_htmltag($search_address).'">';
	print '</td>';
}
if (!empty($arrayfields['t.zip']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
if (!empty($arrayfields['t.town']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
if (!empty($arrayfields['t.max_users']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat searchstring maxwidth75imp" name="search_max_users" value="'.dol_escape_htmltag($search_max_users).'">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], "t.ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['ty.label']['checked'])) {
	print_liste_field_titre($arrayfields['ty.label']['label'], $_SERVER["PHP_SELF"], "ty.label", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.address']['checked'])) {
	print_liste_field_titre($arrayfields['t.address']['label'], $_SERVER["PHP_SELF"], "t.address", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.zip']['checked'])) {
	print_liste_field_titre($arrayfields['t.zip']['label'], $_SERVER["PHP_SELF"], "t.zip", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.town']['checked'])) {
	print_liste_field_titre($arrayfields['t.town']['label'], $_SERVER["PHP_SELF"], "t.town", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.max_users']['checked'])) {
	print_liste_field_titre($arrayfields['t.max_users']['label'], $_SERVER["PHP_SELF"], "t.max_users", "", $param, "", $sortfield, $sortorder);
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";


$totalarray = array();

if ($ret) {
	foreach ($object->lines as $resource) {
		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print '<a class="editfielda" href="./card.php?action=edit&token='.newToken().'&id='.$resource->id.'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="./card.php?action=delete&token='.newToken().'&id='.$resource->id.'">';
			print img_delete('', 'class="marginleftonly"');
			print '</a>';
			print '</td>';
		}

		if (!empty($arrayfields['t.ref']['checked'])) {
			print '<td>';
			print $resource->getNomUrl(5);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['ty.label']['checked'])) {
			print '<td>';
			print $resource->type_label;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['t.address']['checked'])) {
			print '<td>';
			print $resource->address;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['t.zip']['checked'])) {
			print '<td>';
			print $resource->zip;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['t.town']['checked'])) {
			print '<td>';
			print $resource->town;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['t.max_users']['checked'])) {
			print '<td>';
			print $resource->max_users;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Extra fields
		$obj = (object) $resource->array_options;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			print '<a class="editfielda" href="./card.php?action=edit&token='.newToken().'&id='.$resource->id.'">';
			print img_edit();
			print '</a>';
			print '&nbsp;';
			print '<a href="./card.php?action=delete&token='.newToken().'&id='.$resource->id.'">';
			print img_delete('', 'class="marginleftonly"');
			print '</a>';
			print '</td>';
		}
		if (!$i) {
			$totalarray['nbfield']++;
		}

		print '</tr>';
	}
} else {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
