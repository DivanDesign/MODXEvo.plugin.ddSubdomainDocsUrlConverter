//<?php
/**
 * ddSubdomainDocsUrlConverter.php
 * @version 1.2 (2016-07-28)
 * 
 * @desc When MODX makes urls a plugin modify them so that the root folder with necessary template has become a subdomain (“http://domain.com/de/about” → “http://de.domain.com/about” or just “about” in the “de” document and its descendants).
 * 
 * @uses MODXEvo >= 1.1.
 * @uses The library modx.ddTools 0.14.2.
 * 
 * @param $subdomainDocsTemplateId {integer} — Template ID of the root folder-subdomain. @required
 * @param $alwaysBuildAbsoluteUrl {'yes'|'no'} — Convert all URLs to absolute. Default: 'no'.
 * @param $fullUrlDefaultSubdomain {string} — Default subdomain for full URLs. Default: ''.
 * 
 * @config &subdomainDocsTemplateId=Template id of subdomain documents;text; &alwaysBuildAbsoluteUrl=Always build an absolute URL;list;yes,no;no &fullUrlDefaultSubdomain=Default subdomain for full URLs;text;www
 * @event OnMakeDocUrl
 * 
 * @link http://code.divandesign.biz/modx/ddsubdomaindocsurlconverter/1.2
 * 
 * @copyright 2015–2016 DivanDesign {@link http://www.DivanDesign.biz }
 */

if (
	!isset($subdomainDocsTemplateId) ||
	!is_numeric($subdomainDocsTemplateId)
){
	return;
}

if ($modx->Event->name == 'OnMakeDocUrl'){
	$alwaysBuildAbsoluteUrl = isset($alwaysBuildAbsoluteUrl) && $alwaysBuildAbsoluteUrl == 'yes' ? true : false;
	$fullUrlDefaultSubdomain = isset($fullUrlDefaultSubdomain) ? $fullUrlDefaultSubdomain : '';
	
	//Подключаем modx.ddTools
	require_once $modx->getConfig('base_path').'assets/libs/ddTools/modx.ddtools.class.php';
	
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
	$currentSubdomainAlias = !isset($currentSubdomainAlias[2]) ? $fullUrlDefaultSubdomain : $currentSubdomainAlias[2];
	
	//Разберём url текущего документа
	$url = parse_url($modx->Event->params['url']);
	
	//Допишем недостающий слэш вначале пути при необходимости
	if (substr($url['path'], 0, 1) != '/'){$url['path'] = '/'.$url['path'];}
	
	//По умолчанию считаем, что строим ссылку на страницу без поддомена (ну, а какая разница?)
	$buildSubdomainAlias = $fullUrlDefaultSubdomain;
	
	//Если ссылка формируется на одну из страниц в папке-поддомена
	if ($rootParent['template'] == $subdomainDocsTemplateId){
		$buildSubdomainAlias = $rootParent['alias'];
		
		//Убираем псевдоним папки-поддомена из начала пути
		$url['path'] = substr($url['path'], strlen('/'.$buildSubdomainAlias));
	}
	
	if (
		//Если нужен всегда полный URL
		$alwaysBuildAbsoluteUrl ||
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
		
		//Если смогли построить какой-то поддомен, то записываем его
		if($buildSubdomainAlias !== ''){
			$url['host'][2] = $buildSubdomainAlias;
		}else{
			//Если нет, то убираем элемент из массива, строим url без поддомена
			unset($url['host'][2]);
		}
		
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