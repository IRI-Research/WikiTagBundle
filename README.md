# WikiTagBundle

WikiTagBundle is a php bundle for [Symfony 2](http://symfony.com/) released by the [Institute for research and innovation](http://www.iri.centrepompidou.fr/) (IRI).
It enables to add semantised tags to any kind of document. 
By semantised, we mean that a tag has its label, but also a wikipedia link related to this label. Right now, for v1.0, it works with the french wikipedia.
For a simple example, the tag "Asie" is related to the link [http://fr.wikipedia.org/wiki/Asie](http://fr.wikipedia.org/wiki/Asie).
The tag can also be categorised, by any value in a list.
The tag can also have an alias, which is any string value.
When a wikipedia entry is found, the bundle also searches a [dbPedia](http://dbpedia.org/) entry for the english equivalent, for example [http://dbpedia.org/page/Asia](http://dbpedia.org/page/Asia).

A tag can have 4 kinds of wikipedia links :

* **match** - Perfect match between the label and the wikipedia entry (i.e. [Asie](http://fr.wikipedia.org/wiki/Asie)).
* **redirection** - The label exists in wikipedia but redirects to an other entry (i.e. Louis XIV to [Louis XIV de France](http://fr.wikipedia.org/wiki/Louis_XIV_de_France)).
* **homonymy** - The label leads to wikipedia homonymy page (i.e. [Abstraction](http://fr.wikipedia.org/wiki/Abstraction)).
* **null result** - The label is not related to any wikipedia entry. So we build a link leading to the search page (i.e. [art multimédia](http://fr.wikipedia.org/w/index.php?search=art+multim%C3%A9dia)).



## Install
WikiTagBundle is a php bundle for [Symfony 2](http://symfony.com/).

* Install the dependencies : [PagerFanta](https://github.com/whiteoctober/Pagerfanta) and [Mondator](https://github.com/mandango/mondator).
* Download the zipfile from the [downloads](https://github.com/) page and install it.
* Once unzipped, just copy the IRI folder in your vendor/bundles folder. The folder hierarchy will be vendor/bundles/IRI/Bundle/WikiTagBundle.

## License

This bundle is under the CeCILL-C license. See the complete license in the bundle:

    Resources/meta/LICENSE

   
## Getting Started
* Install WikiTagBundle
* Register the bundle in AppKernel.php :

         ... new IRI\Bundle\WikiTagBundle\WikiTagBundle(), ...

* Register the namespace in the autoload.php :

         ... 'IRI\Bundle\WikiTagBundle'   => \_\_DIR\_\_.'/../vendor/bundles', ...
    
* Register the namespace fallbacks in the autoload.php :

        $loader->registerNamespaceFallbacks(array(
            \_\_DIR\_\_.'/../src',
            \_\_DIR\_\_.'/cache/dev/wikitag',
            \_\_DIR\_\_.'/cache/prod/wikitag',
            \_\_DIR\_\_.'/cache/test/wikitag',
            \_\_DIR\_\_.'/cache/task/wikitag',
        ));

* Since WikiTagBundle builds its own document class from the host app's document class, you need to tell in config.yml what is the host app's document class and what _text_ fields will be used in this class. 
These fields are used for searching and to calculate the tag's weight. Example :

        wiki_tag:
            document_class: Company\BaseBundle\Entity\Document
            document_id_column: id
            fields:
                title:
                    type: string
                    length: 1024
                    accessor: getTitre
                    weight: 1.0
                description:
                    type: text
                    weight: 0.5
The 'document_id_column' option is used to indicate the primary key column used by the host app's document class. We are currently limited to non composite primary keys.
A field definition has the following format:
<field name>:
    type: <string or text> : field type. default : text 
    length: <int> : the length of the field. ignored if field type is text
    accessor: <field name or method name> : the field name in the host app's document class, or the name of the method used to access the field value. If not found it will try ta add 'get' in frint of the name. Default : the field name
    weight: <float> : the weight used for this field to calculate the score of each tag. default : 1.0 

* Add the WikiTag routes to your routing.yml :

        WikiTagBundle:
            resource: "@WikiTagBundle/Resources/config/routing.yml"
            prefix:   /tag

* Run the commands :

        php app/console wikitag:generate-document-class (no need to explain)
        php app/console wikitag:schema:update (also replace and runs php app/console doctrine:schema:update)
        php app/console wikitag:sync-doc (fills the database with the datas from the host document class to the wikitag document class. this command is needed only if the database was not empty)
    
* Your database is ready. You can now include the table of tags in a template. Do not forget css and javascript (and php app/console assets:install). Example :

        {# example of page extending the base template #}
        {% extends 'CompanyBaseBundle:Default:index.html.twig' %}
        
        {% block css_import %}
        {{ parent() }}
        {% render "WikiTagBundle:WikiTag:addCss" %}
        {% endblock %}
        
        {% block js_declaration %}
        {{ parent() }}
        {% render "WikiTagBundle:WikiTag:addJavascript" %}
        {% endblock %}
        
        {% block content %}
        <!-- The beginning of your template -->
                {% render "WikiTagBundle:WikiTag:documentTags" with {'id_doc': doc.id} %}
        <!-- The end of your template -->
        {% endblock %}
  
* Great ! You can now add/remove/change semantised tags to your document ! The WikiTag template includes an autocomplete search field to add simply and fastly any wikipedia semantised tag.


## The list of all tags 
If you want to, you can add into a page the list of all tags. WikiTagBundle manages a list of all tags with the paginator PagerFanta. By default, it displays 50 tags per page.
The template includes links to quick search via a list of tags's first letter. A search field is also included. It works with the star character (\*) as a delimiter for searching.
For example "\*Peter" will return tags ending by Peter, "Peter\*" tags beginning by Peter, and "\*Peter\*" all tags including Peter. 
The list can be sorted in ascending or descending label, number of documents or popularity (integer value).
WikiTagBundle manages pagination, search, and sort with url parameters. 

Example : http://mysite.com/route\_to\_list?searched=Peter\*&num\_page=1&nb\_by\_page=50&sort=popd

Including the tag list template looks like :

        {# example of template including the all tags list #}
        {% extends 'CompanyBaseBundle:Default:index.html.twig' %}
        
        {% block css_import %}
        {{ parent() }}
        {% render "WikiTagBundle:WikiTag:addCss" %}
        {% endblock %}
        
        {% block js_declaration %}
        {{ parent() }}
        {% render "WikiTagBundle:WikiTag:addJavascript" with {'tags_list': true} %}
        {% endblock %}
        
        {% block content %}
        <!-- The beginning of your template -->
            {% render "WikiTagBundle:WikiTag:allTags" %}
        <!-- The end of your template -->
        {% endblock %}

*IMPORTANT* : This template needs a route to be defined in configuration file. Usually, this host site's route leads the a page/document concerned by the clicked tag. 
This route is used by the list to create a link on the "nb of documents" column. config.yml looks like :

        wiki_tag:
            route_for_documents_by_tag: a_route_from_host_site
            document_class: Company\BaseBundle\Entity\Document
            ...


## More configuration 1 : tag list profile for a document
Via config.yml, you can configure which columns are displayed by default for a given user. It also concerns the button "sort tags" 
(This function orders tags depending of their presence in the text fields set in config.yml). 
Is it is very simple, you define the list of columns the user profile will see by default. In the following example, the list values are the ones to use :

        wiki_tag:
            ...
            document_list_profile:
                all:         [ 'sort_tag', 'order', 'id', 'move_up_down', 'label', 'wikipedia_link', 'wikipedia_permalink', 'dbpedia_link', 'category', 'remove_wikipedia_link', 'alias', 'remove_tag_from_list', 'alternative_label', 'alternative_wikipedia_url' ]
                editor :     [ 'order', 'id', 'move_up_down', 'label', 'wikipedia_link', 'wikipedia_permalink', 'dbpedia_link', 'category', 'remove_wikipedia_link', 'remove_tag_from_list' ]
                contributor: [ 'sort_tag', 'order', 'label', 'wikipedia_link', 'wikipedia_permalink', 'delete_wikipedia_link', 'remove_tag_from_list' ]

In these values, "sort_tag" means the sort tag button. All the other values are the available columns in the tag table.
Once this configuration set, call the profile in your template. The profile has to be called in the javascript call AND in the html call. So your template will look like this :

        ...
        {% render "WikiTagBundle:WikiTag:addJavascript" with {'profile_name': 'editor'} %}
        ...
        {% render "WikiTagBundle:WikiTag:documentTags" with {'id_doc': doc.id, 'profile_name': 'editor'} %}
        ...


## More configuration 2 : add context seach to any text part of your page
Via config.yml, you can configure a list of jquery selectors meant to let appear tag context search by selecting text. 
Once some text selected, a little div appears and displays several wikipedia entries with the entry title and a snippet. The results are the same than in an opensearch page 
(example with [découvrir](http://fr.wikipedia.org/w/index.php?search=d%C3%A9couvrir)). If you want this functionality, config.yml will look like this : 

        wiki_tag:
            ...
            reactive_selectors:
                some_divs:  [ '.left_sheet', '#p_title .sheet_title', '#p_description' ]
                only_p:     [ '#p_title .sheet_title', '#p_description' ]
                whole_page: [ 'document' ]

If you want every text on your page to be reactive, the list has to be [ 'document' ].
In the templates, you have to call a specific javascript with the wanted parameter. Your javascript calls may look like this :

        {% block js_declaration %}
        {{ parent() }}
        {% render "WikiTagBundle:WikiTag:addJavascript" with {'profile_name': 'editor'} %}
        {% render "WikiTagBundle:WikiTag:addJavascriptForContextSearch" with {'context_name': 'some_divs'} %}
        {% endblock %}

## More configuration 3 : ignore wikipedia errors
This option allows to ignore wikipedia errors instead of raising an exception. The error is logged with an ERROR level and the tag is added/updated but not semantized.

        wiki_tag:
            ...    
            ignore_wikipedia_error: true

## More configuration 4 : curl parameters
This option enables to add any parameter to the curl requests, since the wikipedia request are made from the server. For example, it is useful when the server is behind a firewall.

        wiki_tag:
            ...    
            curl_options:
            	CURLOPT_HTTPPROXYTUNNEL:  'TRUE'
            	CURLOPT_PROXYAUTH: 'CURLAUTH_NTLM'
            	CURLOPT_USERAGENT: 'Mozilla/5.0 Gecko/20100101 Firefox/10.0.1'
            	CURLOPT_PROXY: 'my.proxy.url:1234'
            	CURLOPT_PROXYUSERPWD: 'MY-DOMAIN\user:password'
            	CURLOPT_PROXYTYPE: 'CURLPROXY_HTTP'

## Services

### Document Service : wiki_tag.document - IRI\Bundle\WikiTagBundle\Services\DocumentService 
The document service gathers methods to manage tags on a given document. all these methods must be called after the host app's document(s) object has been created and flushed.

* addTags : add a tag or a list pf tags to a document. For each new tag, wikipedia will be queried.

* copyTags : copy the tags list from one document to the other.

* reorderTags : reorder a document tags. 

* getTagsLabels : get the list of tag labels from one document.


### Search service : wiki_tag.search - IRI\Bundle\WikiTagBundle\Services\SearchService
The search service allows searching documents

* getTagCloud : returns a weighted list of tag labels. The weight of the label is the number of documents tagged with this label. The list is sorted by descending weights.  

* search : search documents.

## Commands
This bundle provides a number of commans that helps in the tags management.

### wikitag:schema:create
Command to create the database schema. Equivalent to the doctrine:schema:create command.
It fully replaces the doctrine:schema:create and *must* be run instead of the doctrine:schema:create command. 


### wikitag:schema:update
Command to update the database schema. Equivalent to the doctrine:schema:update command.
It fully replaces the doctrine:schema:update and *must* be run instead of the doctrine:schema:update command.


### wikitag:create-fulltext-indexes
Generate the sql to create the full text index on the database. This command is not destined to be directly called.

### wikitag:generate-document-class
Generate the WikiTagBundle document class. This command should not be directly called.

### wikitag:purge-tags
Removes tags associated to no documents.

### wikitag:query-wikipedia
Query wikipedia for informations on tags.

### wikitag:reorder-tags
Automatically reorder documents tags. For each documents treated, each tag of the document is scored according to the fields definition in the bundle configuration. The sorting of the tags is done document by document according to these scores. 

### wikitag:sync-doc
Synchronize the wikiTag Bundle documents with the host bundle. This synchronization is made according to the fields defined in the bundle configuration.

### wikitag:load-fixtures
Allow loading of fixtures to populate the database.

The wikitag document table must exist and be synchronized. There fore the following commands must have been executed:
  
  +  php app/console wikitag:generate-document-class
  +  php app/console wikitag:sync-doc

The wikitag\_document.external\_id field must match the datasheet field fo the taggedsheet table.
This command import in order categories, tags and documents\_tags.
you may have memory problem. In this case you can import data by slices. Here is an example:

  +  all categories : php app/console wikitag:load-fixtures -C /path/to/data.json
  +  all tags : php app/console wikitag:load-fixtures -T /path/to/data.json
  +  20000 first doctags: php app/console wikitag:load-fixtures -D -E 20000 /path/to/data.json
  +  20000 other doctags: php app/console wikitag:load-fixtures -D -B 20001 -E 40000 /path/to/data.json
  +  last doctags: php app/console wikitag:load-fixtures -B 40001 /path/to/data.json

The -B (index Begin) and -E (index End) works alson on the tags. Therefore you cans import tags also in slices.
 
