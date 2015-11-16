//<?php
/**
 * ddDocsSubdomainsUrlConverter.php
 * @version 1.0 (2015-02-22)
 * 
 * @desc При генерации URL средставами MODX изменяет их таким образом, чтобы корневая папка с необходимым шаблоном становилась поддоменом («http://domain.com/de/about» → «http://de.domain.com/about» или просто «about», если находимся в рамках поддомена).
 * 
 * @uses MODXEvo >= 1.0.15_3595d8b791d0dc31ce7c08876c0c0c0a342c73fe.
 * @uses The library modx.ddTools 0.14.2.
 * 
 * @param $subdomainDocTemplateId {integer} — ID шаблона корневой папки-поддомена. @required
 * @param $alwaysBuildFullUrl {'yes'|'no'} — Конвертировать все URL в абсолютные. Default: 'no'.
 * 
 * @config &subdomainDocTemplateId=Template id of subdomain documents;text; &alwaysBuildFullUrl=Always build a full URL;list;yes,no;no
 * @event OnMakeDocUrl
 * 
 * @copyright 2015 DivanDesign {@link http://www.DivanDesign.biz }
 */

if (!isset($subdomainDocTemplateId) || !is_numeric($subdomainDocTemplateId)){return;}

if ($modx->Event->name == 'OnMakeDocUrl'){
	$alwaysBuildFullUrl = isset($alwaysBuildFullUrl) && $alwaysBuildFullUrl == 'yes' ? true : false;
	
	//Подключаем modx.ddTools
	require_once $modx->getConfig('base_path').'assets/snippets/ddTools/modx.ddtools.class.php';
	
	//Получаем корневого родителя
	$rootParent = $modx->getParentIds($modx->Event->params['id']);
	
	if (count($rootParent) > 0){
		$rootParent = array_pop($rootParent);
	}else{
		$rootParent = $modx->Event->params['id'];
	}
	
	//Нам нужен его шаблон и псеводним
	$rootParent = ddTools::getDocument($rootParent, 'template,alias');
	
	//Псевдоним текущего поддомена (для основного домена будет == 'www')
	$currentSubdomainAlias = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
	//Если домена третьего уровня нет (мало ли), значит мы основном домене, иначе — на поддомене
	$currentSubdomainAlias = !isset($currentSubdomainAlias[2]) ? 'www' : $currentSubdomainAlias[2];
	
	//Разберём url текущего документа
	$url = parse_url($modx->Event->params['url']);
	
	//Допишем недостающий слэш вначале пути при необходимости
	if (substr($url['path'], 0, 1) != '/'){$url['path'] = '/'.$url['path'];}
	
	//По умолчанию считаем, что строим ссылку на страницу без поддомена (ну, а какая разница?)
	$buildSubdomainAlias = 'www';
	
	//Если ссылка формируется на одну из страниц в папке-поддомена
	if ($rootParent['template'] == $subdomainDocTemplateId){
		$buildSubdomainAlias = $rootParent['alias'];
		
		//Убираем псевдоним папки-поддомена из начала пути
		$url['path'] = substr($url['path'], strlen('/'.$buildSubdomainAlias));
	}
	
	if (
		//Если нужен всегда полный URL
		$alwaysBuildFullUrl ||
		//Если мы сейчас не на поддомене, на страницу которого строим ссылку, то нужен полный URL (внешняя ссылка)
		$currentSubdomainAlias != $buildSubdomainAlias
	){
		//Добавляем схему при необходимости
		if (!isset($url['scheme'])){$url['scheme'] = !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 'http' : 'https';}
		//Добавляем хост при необходимости
		if (!isset($url['host'])){$url['host'] = $_SERVER['HTTP_HOST'];}
		
		//Разбиваем на домены разных уровней домены
		$url['host'] = array_reverse(explode('.', $url['host']));
		//Нам нужны только 3 уровня доменов (ну мало ли)
		$url['host'] = array_slice($url['host'], 0, 3);
		//Доменом третьего уровня будет необходимый поддомен (как удобно, что у основного будет 'www')
		$url['host'][2] = $buildSubdomainAlias;
		//Склеиваем обратно
		$url['host'] = implode('.', array_reverse($url['host']));
	}
	
	//Собираем URL обратно (очень в упрощённом виде конечно). Было бы здорово использовать «http_build_url», но не везде есть.
	$url['scheme'] = isset($url['scheme']) ? $url['scheme'].'://' : '';
	$url['host'] = isset($url['host']) ? $url['host'] : '';
	$url['query'] = isset($url['query']) ? '?'.$url['query'] : '';
	
	$url = $url['scheme'].$url['host'].$url['path'].$url['query'];
	
	$modx->Event->output($url);
}
//?>