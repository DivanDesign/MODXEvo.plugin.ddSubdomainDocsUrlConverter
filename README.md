# ddSubdomainDocsUrlConverter
При генерации URL средставами MODX изменяет их таким образом, чтобы корневая папка с необходимым шаблоном становилась поддоменом.

## Requires
1. MODXEvo >= 1.0.15_3595d8b791d0dc31ce7c08876c0c0c0a342c73fe
2. MODXEvo.library.ddTools >= 0.14.2

## Events
* OnMakeDocUrl

## Config
`&subdomainDocsTemplateId=Template id of subdomain documents;text;`

## Parameters description
* $subdomainDocsTemplateId {integer} — ID шаблона корневой папки-поддомена.
* [$alwaysBuildAbsoluteUrl='no'] {'yes'|'no'} — Конвертировать все URL в абсолютные.

## Usage
Пусть структура документов будет такая:

* Home (1)
* About (2)
* Blog (3)
	* Article (6)
* _De (4)_
	* Home (7)
	* About (8)
	* Blog (9)
		* Article (12)
* _Fr (5)_
	* Home (10)
	* About (11)

Для документов «De (4)» и «Fr (5)» понадобится отдельный шаблон, назовём его «Subdomain template» (пусть его ID = «4»).

_Параметр плагина `$subdomainDocsTemplateId`, соответственно, выставляем в `4`._

### Когда находимся на одной из страниц основного домена «domain.com»
Адреса страниц при генерации будут преобразованы следующим образом:

* `[~9~]`: `/de/blog` → `http://de.domain.com/blog` (т. к. ссылка на страницу другого домена, она должна быть абсолютной).
* `[~3~]`: `/blog` → `/blog` (ссылка на страницу в рамках текущего домена, так что останется без изменений).
* `[~11~]`: `/fr/about` → `http://fr.domain.com/about`.

Абсолютные ссылки преобразуются аналогичным образом:

* `http://domain.com/de/about` → `http://de.domain.com/about`.
* `http://domain.com/blog` → `http://domain.com/blog`.

### Когда находимся на одной из страниц поддомена «de.domain.com»
* `[~8~]`: `/de/about` → `/about` (внутренняя, т. к. находимся в рамках текущего домена).
* `[~9~]`: `/de/blog` → `/blog`.
* `[~2~]`: `/about` → `http://domain.com/about` (теперь на основной домен необходимо строить внешние ссылки).
* `[~3~]`: `/blog` → `http://domain.com/blog`.
* `[~11~]`: `/fr/about` → `http://fr.domain.com/about` (тоже внешняя).

_Поддомен «www.domain.com» обрабатывается так же, как основной «domain.com»._

## Changelog
### Version 1.0 (2015-02-22)
* \+ The first release.