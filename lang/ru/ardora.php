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
 * Strings for component 'ardora', language 'ru'
 *
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['clicktodownload'] = 'Щелкните по ссылке {$a}, чтобы скачать файл.';
$string['clicktoopen2'] = 'Щелкните по ссылке {$a}, чтобы открыть файл.';
$string['configdisplayoptions'] = 'Выберите все доступные параметры, существующие настройки не будут изменены. Удерживайте клавишу CTRL, чтобы выбрать несколько полей.';
$string['configframesize'] = 'Когда веб-страница или загруженный файл отображаются в рамке, это значение указывает высоту (в пикселях) верхней рамки (которая содержит навигацию).';
$string['configparametersettings'] = 'Этот параметр устанавливает значение по умолчанию для панели настроек параметров в форме при добавлении новых элементов ardora. После первого использования это становится индивидуальной настройкой пользователя.';
$string['configpopup'] = 'При добавлении нового элемента ardora, который может отображаться во всплывающем окне, этот параметр должен быть включен по умолчанию?';
$string['configpopupdirectories'] = 'Должны ли всплывающие окна показывать ссылки на каталоги по умолчанию?';
$string['configpopupheight'] = 'Какую высоту следует установить по умолчанию для новых всплывающих окон?';
$string['configpopuplocation'] = 'Должны ли всплывающие окна отображать адресную строку по умолчанию?';
$string['configpopupmenubar'] = 'Должны ли всплывающие окна отображать меню по умолчанию?';
$string['configpopupresizable'] = 'Должны ли всплывающие окна быть изменяемыми по размеру по умолчанию?';
$string['configpopupscrollbars'] = 'Должны ли всплывающие окна быть прокручиваемыми по умолчанию?';
$string['configpopupstatus'] = 'Должны ли всплывающие окна отображать строку состояния по умолчанию?';
$string['configpopuptoolbar'] = 'Должны ли всплывающие окна отображать панель инструментов по умолчанию?';
$string['configpopupwidth'] = 'Какую ширину следует установить по умолчанию для новых всплывающих окон?';
$string['contentheader'] = 'Содержимое';
$string['displayoptions'] = 'Доступные параметры отображения';
$string['displayselect'] = 'Отображение';
$string['displayselect_help'] = 'Этот параметр вместе с типом файла и возможностью браузера встраивать контент определяет, как будет отображаться файл. Варианты могут включать:

 * Автоматически - Лучший вариант отображения для типа файла выбирается автоматически
 * Встроить - Файл отображается на странице под панелью навигации вместе с описанием файла и любыми блоками
 * Принудительное скачивание - Пользователю предлагается скачать файл
 * Открыть - Только файл отображается в окне браузера
 * Во всплывающем окне - Файл отображается в новом окне браузера без меню и адресной строки
 * В рамке - Файл отображается в рамке под панелью навигации и описанием файла
 * Новое окно - Файл отображается в новом окне браузера с меню и адресной строкой';
$string['displayselect_link'] = 'mod/file/mod';
$string['displayselectexplain'] = 'Выберите тип отображения, к сожалению, не все типы подходят для всех файлов.';
$string['dnduploadardora'] = 'Создать файл ardora';
$string['encryptedcode'] = 'Зашифрованный код';
$string['filenotfound'] = 'Файл не найден, извините.';
$string['filterfiles'] = 'Использовать фильтры для содержимого файла';
$string['filterfilesexplain'] = 'Выберите тип фильтрации содержимого файла, обратите внимание, что это может вызвать проблемы для некоторых апплетов Flash и Java. Убедитесь, что все текстовые файлы имеют кодировку UTF-8.';
$string['filtername'] = 'Автоматическая привязка имен ardora';
$string['forcedownload'] = 'Принудительное скачивание';
$string['framesize'] = 'Высота рамки';
$string['indicator:cognitivedepth'] = 'Когнитивная глубина файла';
$string['indicator:cognitivedepth_help'] = 'Этот индикатор основан на когнитивной глубине, достигнутой студентом в файле ardora.';
$string['indicator:cognitivedepthdef'] = 'Когнитивная глубина файла';
$string['indicator:cognitivedepthdef_help'] = 'Участник достиг этого процента когнитивного вовлечения, предложенного файлами ardora, в течение этого периода анализа (Уровни = Нет просмотра, Просмотр)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Социальная широта файла';
$string['indicator:socialbreadth_help'] = 'Этот индикатор основан на социальной широте, достигнутой студентом в файле ardora.';
$string['indicator:socialbreadthdef'] = 'Социальная широта файла';
$string['indicator:socialbreadthdef_help'] = 'Участник достиг этого процента социального вовлечения, предложенного файлами ardora, в течение этого периода анализа (Уровни = Нет участия, Участник в одиночку)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['legacyfiles'] = 'Миграция старых файлов курса';
$string['legacyfilesactive'] = 'Активно';
$string['legacyfilesdone'] = 'Завершено';
$string['modifieddate'] = 'Изменено {$a}';
$string['modulename'] = 'Ardora';
$string['modulename_help'] = 'Объяснительная помощь здесь';
$string['modulename_link'] = 'mod/ardora/view';
$string['modulenameplural'] = 'Файлы';
$string['notmigrated'] = 'Этот унаследованный тип ardora ({$a}) еще не был перенесен, извините.';
$string['optionsheader'] = 'Параметры отображения';
$string['page-mod-ardora-x'] = 'Любая страница модуля файла';
$string['pluginadministration'] = 'Администрирование модуля файла';
$string['pluginname'] = 'Ardora';
$string['popupheight'] = 'Высота всплывающего окна (в пикселях)';
$string['popupheightexplain'] = 'Указывает высоту по умолчанию для всплывающих окон.';
$string['popupardora'] = 'Этот ardora должен отображаться во всплывающем окне.';
$string['popupardoralink'] = 'Если не появилось, щелкните здесь: {$a}';
$string['popupwidth'] = 'Ширина всплывающего окна (в пикселях)';
$string['popupwidthexplain'] = 'Указывает ширину по умолчанию для всплывающих окон.';
$string['printintro'] = 'Отобразить описание ardora';
$string['printintroexplain'] = 'Отображать описание ardora под содержимым? Некоторые типы отображения могут не показывать описание, даже если оно включено.';
$string['privacy:metadata'] = 'Плагин ardora не хранит персональные данные.';
$string['ardora:addinstance'] = 'Добавить новый ardora';
$string['ardoracontent'] = 'Файлы и подпапки';
$string['ardoradetails_sizetype'] = '{$a->size} {$a->type}';
$string['ardoradetails_sizedate'] = '{$a->size} {$a->date}';
$string['ardoradetails_typedate'] = '{$a->type} {$a->date}';
$string['ardoradetails_sizetypedate'] = '{$a->size} {$a->type} {$a->date}';
$string['ardora:exportardora'] = 'Экспортировать ardora';
$string['ardora:view'] = 'Просмотр ardora';
$string['search:activity'] = 'Ardora';
$string['selectmainfile'] = 'Пожалуйста, выберите основной файл, щелкнув по значку рядом с именем файла.';
$string['showdate'] = 'Показать дату загрузки/изменения';
$string['showdate_desc'] = 'Показать дату загрузки/изменения на странице курса?';
$string['showdate_help'] = 'Показывает дату загрузки/изменения рядом со ссылками на файл.

Если в этом ardora несколько файлов, отображается дата загрузки/изменения основного файла.';
$string['showsize'] = 'Показать размер';
$string['showsize_help'] = 'Показывает размер файла, например "3.1 МБ", рядом со ссылками на файл.

Если в этом ardora несколько файлов, отображается общий размер всех файлов.';
$string['showsize_desc'] = 'Показать размер файла на странице курса?';
$string['showtype'] = 'Показать тип';
$string['showtype_desc'] = 'Показать тип файла (например, "Документ Word") на странице курса?';
$string['showtype_help'] = 'Показывает тип файла, например "Документ Word", рядом со ссылками на файл.

Если в этом ardora несколько файлов, отображается тип основного файла.

Если тип файла не известен системе, он не будет отображаться.';
$string['uploadeddate'] = 'Загружено {$a}';
$string['embedheightexplain'] = 'Высота сцены.';
$string['embedwidthexplain'] = 'Ширина сцены.';
$string['embedwidth'] = 'Ширина';
$string['embedheight'] = 'Высота';
