var nodearray;
(function ($) {
    'use strict';
    var ajax_url = (wcfefg_action_obj.ajax_url !== undefined) ? wcfefg_action_obj.ajax_url : null;
    var post_id = (wcfefg_action_obj.post_id !== undefined) ? parseInt(wcfefg_action_obj.post_id) : null;
    jQuery('#wcfefg_custom_fields #newmeta-submit').on("click", function (event) {
        event.preventDefault();
        var metakeyinput = jQuery(this).parents("#wcfefg_custom_fields").find("#metakeyinput").val();
        var metavalue = jQuery(this).parents("#wcfefg_custom_fields").find("#metavalue").val();
        var metakeyselectval = jQuery("#wcfefg_custom_fields #newmeta #metakeyselect").val();
        var ajax_nonce_add_meta = jQuery(this).parents("#wcfefg_custom_fields").find("#_ajax_nonce-add-meta").val();
        if (ajax_nonce_add_meta !== null && metavalue !== null) {
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    '_ajax_nonce': 0,
                    'action': 'add-meta',
                    'metakeyselect': metakeyselectval,
                    'metakeyinput': metakeyinput,
                    'metavalue': metavalue,
                    '_ajax_nonce-add-meta': ajax_nonce_add_meta,
                    'post_id': post_id
                },
                success: function (data, textStatus, XMLHttpRequest) {
                    if (XMLHttpRequest.responseXML !== undefined) {
                        nodearray={};
                        var doc = new DOMParser().parseFromString(XMLHttpRequest.responseXML.documentElement.children[0].childNodes[0].childNodes[0].textContent, "text/xml");
                        // nodearray={};     
                        // console.log(XMLHttpRequest.responseXML.documentElement.children[0].childNodes[0].childNodes[0].textContent);
                         getchild(doc,jQuery("#wcfefg_custom_fields #list-table tbody").get(0));

                        jQuery("#wcfefg_custom_fields #newmeta #metakeyinput, #wcfefg_custom_fields #newmeta #metavalue").val('');
                        jQuery("#wcfefg_custom_fields #newmeta #metakeyselect").get(0).selectedIndex = 0;
                        if (!jQuery('#wcfefg_custom_fields #list-table').is(':visible')) {
                            jQuery('#wcfefg_custom_fields #list-table').attr("style", "");
                        }
                    }
                }
            });
        }
        return false;
    });
    jQuery(document).on('click', '#wcfefg_custom_fields div.submit .deletemeta', function (event) {
        event.preventDefault();
        var metaID, ajax_nonce_delete_meta;
        if (jQuery(this).attr("data-delete-key") !== undefined) {
            metaID = jQuery(this).attr("data-delete-key");
        } else {
            metaID = jQuery(this).parents("tr").attr("id");
            metaID = metaID.replace("meta-", "");
        }
        if (jQuery(this).attr("data-delete-nonce") !== undefined ) {
            ajax_nonce_delete_meta = jQuery(this).attr("data-delete-nonce");
        } else {
            ajax_nonce_delete_meta = $(this).attr("data-wp-lists");
            ajax_nonce_delete_meta = ajax_nonce_delete_meta.substr(ajax_nonce_delete_meta.indexOf("_ajax_nonce=") + 12);
        }
        var _this = jQuery(this);
        if (metaID !== null && ajax_nonce_delete_meta !== null) {
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    'action': 'delete-meta',
                    'id': metaID,
                    '_ajax_nonce': ajax_nonce_delete_meta
                },
                success: function (data) {
                    _this.parents("tr#meta-" + metaID).remove();
                }
            });
        }
        return false;
    });
    jQuery(document).on('click', '#wcfefg_custom_fields div.submit .updatemeta', function (event) {
        event.preventDefault();
        var metaID, ajax_nonce_update_meta;
        var ajax_nonce = jQuery(this).parents("td").find("#_ajax_nonce").val();

        if (jQuery(this).attr("data-update-key") !== undefined) {
            metaID = jQuery(this).attr("data-update-key");
        } else {
            metaID = jQuery(this).parents("tr").attr("id");
            metaID = metaID.replace("meta-", "");
        }

        if (jQuery(this).attr("data-update-nonce") !== undefined ) {
            ajax_nonce_update_meta = jQuery(this).attr("data-update-nonce");
        } else {
            ajax_nonce_update_meta = $(this).attr("data-wp-lists");
            ajax_nonce_update_meta = ajax_nonce_update_meta.substr(ajax_nonce_update_meta.indexOf("add-meta=") + 9);
        }

        var metakeyfield = "meta[" + metaID + "][key]";
        var metakeyfieldvalue = jQuery("#meta-" + metaID + "-key").val();
        var metavalfield = "meta[" + metaID + "][value]";
        var metavalfieldvalue = jQuery("#meta-" + metaID + "-value").val();
        if (ajax_nonce !== null && ajax_nonce_update_meta !== null) {
            var requestParam = $.parseJSON('{"_ajax_nonce": "' + ajax_nonce + '", "action": "add-meta", "_ajax_nonce-add-meta": "' + ajax_nonce_update_meta + '", "' + metakeyfield + '": "' + metakeyfieldvalue + '", "' + metavalfield + '": "' + metavalfieldvalue + '", "post_id": ' + post_id + '}');
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: requestParam,
                success: function (data, textStatus, XMLHttpRequest) {}
            });
            jQuery(this).blur();
        }
        return false;
    });
})(jQuery);


function getchild(element,tbody){
        var nodes=element.childNodes;
        for(var i=0; i<nodes.length;i++){
            if(nodes[i].nodeName!='#text'){
                var attrs = nodes[i].attributes;
                var f=0;

                console.log(JSON.stringify(attrs));
                var ele=createtag(tbody,nodes[i].nodeName,attrs)
            }
            else if(nodes[i].nodeName=='#text' && element.nodeName=='textarea'){
              
                setText(tbody,element.textContent);

            }
            
            if(nodes[i].childNodes.length>0){
               getchild(nodes[i],ele) 
            }    
        }
}

function createtag(element,tag,attributes){
    var tag=document.createElement(tag);
    var id=setAllAttributes(tag,attributes);
    element.appendChild(tag);
    return document.getElementById(id);    
}
function setAllAttributes(element,attributes){
    var f=0, ran;
    for(var k=0; k< attributes.length;k++){
        if(attributes[k].name=='id'){
            f=1;
            ran=attributes[k].value;
        }
        element.setAttribute(attributes[k].name, attributes[k].value);
     }
     if(f==0){
        ran=makeid(6);
        element.setAttribute("id", ran);     
     } 
    return ran;
} 
function makeid(length) {
   var result           = '';
   var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
   var charactersLength = characters.length;
   for ( var i = 0; i < length; i++ ) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
   }
   return result;
}
function setText(element,text){
    var cell = document.createTextNode(text);
    element.appendChild(cell);
}