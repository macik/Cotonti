<?PHP
/**
 * Russian Language File for Tags Plugin
 *
 * @package Cotonti
 * @version 0.1.0
 * @author Cotonti Translators Team
 * @copyright Copyright (c) Cotonti Team 2008-2009
 * @license BSD
 */

defined('SED_CODE') or die('Wrong URL.');

/**
 * Plugin Body
 */

$L['tags_All'] = 'Все теги';
$L['tags_comma_separated'] = 'разделяя запятой';
$L['tags_Keyword'] = 'Ключевое слово';
$L['tags_Keywords'] = 'Ключевые слова';
$L['tags_Query_hint'] = 'Несколько тегов, разделённых запятой, означают логическое И между ними. Вы также можете использовать точку с запятой в качестве логического ИЛИ. И имеет высший приоритет над ИЛИ. Вы не можете использовать скобки для группировки условий. Звёздочка (*) внутри тега используется в качестве маски для &quot;подстроки&quot;.';
$L['tags_Search_results'] = 'Результаты поиска';
$L['tags_Tag_cloud'] = 'Облако тегов';
$L['tags_Tag_cloud_none'] = 'Нет тегов';

/**
 * Plugin Config
 */

$L['cfg_forums'] = array('Включить теги для форумов');
$L['cfg_limit'] = array('Максимальное количество тегов','0 &mdash; неограничено');
$L['cfg_lim_forums'] = array('Лимит количества тегов в облаке на форумах','0 &mdash; неограничено');
$L['cfg_lim_index']= array('Лимит количества тегов в облаке на главной странице', '0 &mdash; неограничено');
$L['cfg_lim_pages'] = array('Лимит количества тегов в облаке на страницах','0 &mdash; неограничено');
$L['cfg_more'] = array('Показывать в облаке тегов ссылку "Все теги"');
$L['cfg_order'] = array('Сортировка облака тегов','по алфавиту, по убыванию частотности, случайным образом');
$L['cfg_pages'] = array('Включить теги для страниц');
$L['cfg_title'] = array('Использовать первое из последних ключевых слов');
$L['cfg_translit'] = array('Транслитерировать Теги в URL-адресах');

?>