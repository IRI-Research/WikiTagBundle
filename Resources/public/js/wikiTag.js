// -*- coding: utf-8 -*-
/*
 * This file is part of the WikiTagBundle package.
 *
 * (c) IRI <http://www.iri.centrepompidou.fr/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function wikitag_init_tags_events()
{
    // Tag simple operations : activate/unactivate wp link, reset wp info, remove wp link, remove tag from list
    $(".wikitag_reset_wp_info").click(function(e){
        if(confirm("Confirmez-vous le rétablissement du label original de ce tag ?")){
        	var id_tag = $(this).html();
        	var btn = this;
        	$(this).html("<img src='"+static_url+"/images/indicator.gif'>");
            wikitag_update_tag(this, reset_wp_info_url, id_tag, function(){ $(btn).html(id_tag); });
        }
    });
    $(".wikitag_relaunch_wp_search").click(function(e){
        if(confirm("Confirmez-vous la relance de la recherche Wikipédia pour le tag \"" + $(this).attr('alt') + "\" ?")){
        	$(this).toggleClass("wikitag_relaunch_wp_search wikitag_indicator");
        	var btn = this;
            wikitag_update_tag(this, relaunch_wp_search_url, $(this).attr('id'), function() { $(btn).toggleClass("wikitag_relaunch_wp_search wikitag_indicator");});
        }
    });
    $(".wikitag_remove_wp_link").click(function(e){
        if(confirm("Confirmez-vous le suppression du lien Wikipédia pour le tag \"" + $(this).attr('alt') + "\" ?")){
            wikitag_update_tag(this, remove_wp_link_url, $(this).attr('id'));
        }
    });
    $(".wikitag_remove_tag_from_list").click(function(){
        if(confirm("Confirmez-vous la suppression du tag \"" + $(this).attr('alt') + "\" de la liste courante ?")){
            wikitag_update_tag(this,remove_tag_from_list_url,$(this).attr('id'));
        }
    });
    
    // Wikipedia search management (autocompletion and save changes)
    $.editable.addInputType('autocomplete', {
    	element : $.editable.types.text.element,
    	plugin : function(settings, original) {
    		$('input', this).autocomplete(settings.autocomplete);
    	}
    });
    $(".wikipediatag").editable(modify_tag_url, { 
    	indicator : "<img src='"+static_url+"images/indicator.gif'>",
    	type      : "autocomplete",
    	tooltip   : "Cliquer pour éditer...",
    	onblur    : "submit",
    	submitdata: {
            csrfmiddlewaretoken:global_csrf_token,
            wikitag_document_id:$('#wikitag_document_id').val(),
            wikitag_document_profile:$('#wikitag_document_profile').val(),
            num_page:(('num_page' in getUrlVars()) ? getUrlVars()['num_page'] : undefined),
            nb_by_page:(('nb_by_page' in getUrlVars()) ? getUrlVars()['nb_by_page'] : undefined),
            sort:((('sort' in getUrlVars()) && (typeof(getUrlVars()['sort'])=="string")) ? getUrlVars()['sort'] : undefined),
            searched:(('searched' in getUrlVars()) ? getUrlVars()['searched'] : undefined)
        },
    	callback  : function(value, settings) {
            $('#wikitag_table_container').html(value);
            wikitag_init_tags_events();
    	},
		onerror: function(settings, original, jqXHR) {
			resp = $.parseJSON(jqXHR.responseText);
			alert(resp.message);
			original.reset();
		},
    	autocomplete : {
			source: function( request, response ) {
				$.ajax({
					url: "http://fr.wikipedia.org/w/api.php",
					dataType: "jsonp",
					data: {
						action: "opensearch",
						limit: "20",
						namespace: "0",
						format: "json",
						search: request.term
					},
					success: function( data ) {
						response( $.map( data[1], function( item ) {
							return {
								label: item,
								value: item
							};
						}));
					}
				});
			},
			minLength: 2,
			open: function() {
				$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
			},
			close: function() {
				$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
			}
    	}
    });
    
    // Update alias management
    $(".wikitag_alias").editable(update_tag_alias_url, {
    	indicator : "<img src='"+static_url+"images/indicator.gif'>",
    	type      : "text",
    	placeholder:"",
    	tooltip   : "Cliquer pour éditer...",
    	onblur    : "submit",
    	submitdata: {
            csrfmiddlewaretoken:global_csrf_token,
            wikitag_document_id:$('#wikitag_document_id').val(),
            wikitag_document_profile:$('#wikitag_document_profile').val(),
            num_page:(('num_page' in getUrlVars()) ? getUrlVars()['num_page'] : undefined),
            nb_by_page:(('nb_by_page' in getUrlVars()) ? getUrlVars()['nb_by_page'] : undefined),
            sort:((('sort' in getUrlVars()) && (typeof(getUrlVars()['sort'])=="string")) ? getUrlVars()['sort'] : undefined),
            searched:(('searched' in getUrlVars()) ? getUrlVars()['searched'] : undefined)
        },
    	callback  : function(value, settings) {
            $('#wikitag_table_container').html(value);
            wikitag_init_tags_events();
    	}
    });
    
    // Tag categories management
    $(".wikitag_category").editable(update_tag_category_url, {
    	indicator : "<img src='"+static_url+"/images/indicator.gif'>",
    	type      : "select",
        data      : categories_list,
    	placeholder:"",
    	tooltip   : "Cliquer pour éditer...",
    	onblur    : "submit",
    	submitdata: {
            csrfmiddlewaretoken:global_csrf_token,
            wikitag_document_id:$('#wikitag_document_id').val(),
            wikitag_document_profile:$('#wikitag_document_profile').val(),
            num_page:(('num_page' in getUrlVars()) ? getUrlVars()['num_page'] : undefined),
            nb_by_page:(('nb_by_page' in getUrlVars()) ? getUrlVars()['nb_by_page'] : undefined),
            sort:((('sort' in getUrlVars()) && (typeof(getUrlVars()['sort'])=="string")) ? getUrlVars()['sort'] : undefined),
            searched:(('searched' in getUrlVars()) ? getUrlVars()['searched'] : undefined)
        },
    	callback  : function(value, settings) {
            $('#wikitag_table_container').html(value);
            wikitag_init_tags_events();
    	}
    });
    
    // Tag table drag and drop
    $("#wikitag_table").tableDnD({
        onDragClass: "wikitag_dragged_row",
        onDrop: function(table, row){
            old_order = row.id;
            $($(row).children()[1]).html("<img src='"+static_url+"/images/indicator.gif'/>");
            rows = table.tBodies[0].rows;
            nb_rows = rows.length;
            for(var i=0; i<nb_rows; i++){
                if(rows[i].id==old_order){
                    new_order = i+1;
                    $.ajax({
                        url: tag_up_down_url,
                        type: 'POST',
                        data: {csrfmiddlewaretoken:global_csrf_token, 
                               wikitag_document_id:$('#wikitag_document_id').val(),
                               wikitag_document_profile:$('#wikitag_document_profile').val(),
                               new_order:new_order,
                               old_order:old_order
                               },
                        // bug with jquery >= 1.5, "json" adds a callback so we don't specify dataType
                        //dataType: 'json',
                        success: function(msg, textStatus, XMLHttpRequest) {
                            $('#wikitag_table_container').html(msg);
                            wikitag_init_tags_events();
                        }
                    });
                }
            }
        },
        dragHandle: "wikitag_updown_td"
    });
    // Hide/show column management.
    $('#wikitag_table').columnManager({listTargetID:'wikitag_ul_target', onClass: 'wikitag_advon', offClass: 'wikitag_advoff', hideInList: notInList, saveState: true, colsHidden:columsToHide });
    //create the clickmenu from the target
    $('#wikitag_ulSelectColumn').clickMenu({onClick: function(){}});
}

function wikitag_init_datasheet_events()
{
    var select_done = false;
    // Wikipedia search management (new tag)
    $("#wikitag_wp_search").autocomplete({
        source: function( request, response ) {
            $.ajax({
                url: "http://fr.wikipedia.org/w/api.php",
                dataType: "jsonp",
                data: {
                    action: "opensearch",
                    limit: "20",
                    namespace: "0",
                    format: "json",
                    search: request.term
                },
                success: function( data ) {
                    response( $.map( data[1], function( item ) {
                        return {
                            label: item,
                            value: item
                        };
                    }));
                }
            });
        },
        select: function(event, ui) { 
            // Since the event still did not update wp_search's val, we force it.
            $("#wikitag_wp_search").val(ui.item.label);
            select_done = true;
            $("#wikitag_ok_search").click();
        },
        minLength: 2,
        open: function() {
            $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
        },
        close: function() {
            $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
        }
    });
    $('#wikitag_wp_search').keyup(function(e){
        if((e.keyCode==13) && ($("#wikitag_wp_search").val()!="") && (select_done==false)){
            add_tag($("#wikitag_wp_search").val());
        }
        select_done = false;
    });
    $("#wikitag_ok_search").click(function(){
        if($("#wikitag_wp_search").val()!=""){
            add_tag($("#wikitag_wp_search").val());
        }
    });
    $("#wikitag_tags_sort").click(function(e){
    	 e.preventDefault();
    	if(confirm("Confirmez-vous le tri des tags ?")) {
    		reorder_tags();
    	}
    });
}

function wikitag_init_search_context_events()
{
    // We implement the behaviour on text select. Kolich is just an object name, it could be anything
    if(!window.Kolich){
        Kolich = {};
    }
    Kolich.Selector = {};
    Kolich.Selector.getSelected = function(){
        var t = '';
        if(window.getSelection){
            t = window.getSelection();
        }else if(document.getSelection){
            t = document.getSelection();
        }else if(document.selection){
            t = document.selection.createRange().text;
        }
        return t;
    };
    Kolich.Selector.mouseup = function(e){
      var st = Kolich.Selector.getSelected();
      if(st!=''){
        // Behaviour after the text was selected
		var o = 0;
	    if($(window)){
	        o = $(window).scrollTop();
	    }else if($(document)){
	        o = $(document).scrollTop();
	    }
        $("#wikitag_context_div").offset({left:e.pageX+10,top:e.pageY+o});
        $("#wikitag_context_div").show();
        $("#wikitag_context_div #wikitag_wp_search_context").val(st);
        $("#wikitag_context_div #wikitag_wp_search_context").autocomplete("search");
      }
    };
    $(document).ready(function(){
        $("#wikitag_context_div").offset({left:0,top:0});
        for(c in reactive_selectors){
            $(reactive_selectors[c]).bind("mouseup", Kolich.Selector.mouseup);
        }
    });
    
    // Function to close the context window
    $("#wikitag_context_close").click(function(e){
        $("#wikitag_context_div #wikitag_wp_search_context").autocomplete("close");
        $("#wikitag_context_div").offset({left:0,top:0});
        $("#wikitag_context_div").hide();
    });
    
    // Wikipedia search management (new tag)
    $("#wikitag_wp_search_context").autocomplete({
        source: function( request, response ) {
            $.ajax({
                url: "http://fr.wikipedia.org/w/api.php",
                dataType: "jsonp",
                data: {
                    action: "query",
                    limit: "20",
                    list: "search",
                    format: "json",
                    srsearch: request.term
                },
                success: function( data ) {
                    response( $.map( data["query"]["search"], function( item ) {
                        return {
                            label: item["title"],
                            snippet: item["snippet"],
                            value: item["title"]
                        };
                    }));
                }
            });
        },
        select: function(event, ui) { 
            // Since the event still did not update wp_search's val, we force it.
            $("#wikitag_wp_search_context").val(ui.item.label);
            add_tag($("#wikitag_wp_search_context").val());
            $("#wikitag_context_close").click();
        },
        minLength: 2,
        open: function() {
            $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
            // We force width to something not too large
            $( this ).autocomplete("widget").addClass("wikitag_context_result");
        },
        close: function() {
            $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
        }
    });
    $("#wikitag_wp_search_context").data("autocomplete")._renderItem = function( ul, item ) {
        return $( "<li></li>" )
        .data( "item.autocomplete", item )
        .append( '<a class="wikitag_context_result_item"><span class="wikitag_context_result_title">'+ item.label + '</span> : <span class="wikitag_context_result_snippet">' + item.snippet + '</span></a>' )
        .appendTo( ul );
    };
    $('#wikitag_wp_search_context').keyup(function(e){
        if((e.keyCode==13) && ($("#wikitag_wp_search_context").val()!="")){
            add_tag($("#wikitag_wp_search_context").val());
        }
    });
}

function wikitag_update_tag(btn, url, id_tag, error_callback)
{
    new_checked = false;
    // 2 cases : 
    // - ordered tag for one datasheet : $('#wikitag_document_id') is not null
    // - all tags list : $('#wikitag_document_id') is null and $('#num_page') and $('#nb_by_page') are not null
    $.ajax({
        url: url,
        type: 'POST',
        data: {csrfmiddlewaretoken:global_csrf_token, 
               wikitag_document_id:$('#wikitag_document_id').val(),
               wikitag_document_profile:$('#wikitag_document_profile').val(),
               num_page:(('num_page' in getUrlVars()) ? getUrlVars()['num_page'] : undefined),
               nb_by_page:(('nb_by_page' in getUrlVars()) ? getUrlVars()['nb_by_page'] : undefined),
               sort:((('sort' in getUrlVars()) && (typeof(getUrlVars()['sort'])=="string")) ? getUrlVars()['sort'] : undefined),
               searched:(('searched' in getUrlVars()) ? getUrlVars()['searched'] : undefined),
               tag_id:id_tag,
               activated:new_checked
               },
        // bug with jquery >= 1.5, "json" adds a callback so we don't specify dataType
        //dataType: 'json',
        success: function(msg, textStatus, XMLHttpRequest) {
            $('#wikitag_table_container').html(msg);
            wikitag_init_tags_events();
        },
		error: function(jqXHR, textStatus, errorThrown) {
			resp = $.parseJSON(jqXHR.responseText);
			alert(resp.message);
			error_callback && error_callback();
		}
    });
}


function add_tag(tag_label)
{
    $("#wikitag_ok_search").html("<img src='"+static_url+"/images/indicator.gif'>");
    var url = add_tag_url;
    $.ajax({
        url: url,
        type: 'POST',
        data: {csrfmiddlewaretoken:global_csrf_token,
               wikitag_document_id:$('#wikitag_document_id').val(),
               wikitag_document_profile:$('#wikitag_document_profile').val(),
               value:tag_label
               },
        // bug with jquery >= 1.5, "json" adds a callback so we don't specify dataType
        //dataType: 'json',
        success: function(msg, textStatus, XMLHttpRequest) {
            $('#wikitag_table_container').html(msg);
            wikitag_init_tags_events();
            // And scroll to the bottom
            $("html").animate({ scrollTop: $(document).height() }, 500);
        },
        error: function(jqXHR, textStatus, errorThrown) {
			resp = $.parseJSON(jqXHR.responseText);
			alert(resp.message);
        },
        complete: function(){
            // We empty the input and hide the ok button
            $("#wikitag_wp_search").val("");
            $("#wikitag_ok_search").html("<b>OK</b>");
        }
    });
}

function reorder_tags() {
	$('#wikitag_tags_sort').attr("disabled", "disabled");
	var tag_sort_old_src = $("#wikitag_tags_sort").attr("src");
	$("#wikitag_tags_sort").attr("src",static_url+"images/indicator.gif");
	$.ajax({
		url: reorder_tag_datasheet_url,
		type: 'POST',
		data: {
			csrfmiddlewaretoken:global_csrf_token,
            wikitag_document_id:$('#wikitag_document_id').val(),
            wikitag_document_profile:$('#wikitag_document_profile').val()
		},
        success: function(msg, textStatus, XMLHttpRequest) {
            $('#wikitag_table_container').html(msg);
            wikitag_init_tags_events();
            // And scroll to the bottom
            $("html").animate({ scrollTop: $(document).height() }, 500);
            //TODO ; translate
        	alert("Important : le pré-classement automatique est terminé. Veuillez affiner l’ordre des tags manuellement.");            
        },
        complete: function(){
        	$("#wikitag_tags_sort").attr("src",tag_sort_old_src);
        	$('#wikitag_tags_sort').removeAttr("disabled");
        }
	});
}

function getUrlVars()
{
    var vars = [], hash;
    if(window.location.href.indexOf('?')>=0){
	    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++)
	    {
	        hash = hashes[i].split('=');
	        vars.push(hash[0]);
	        vars[hash[0]] = hash[1];
	    }
    }
    return vars;
}
