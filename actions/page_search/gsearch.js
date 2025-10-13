;
"use strict";
(function ($) {
    var content_main = '';
    var options = '';
    var element = '';
    var search_element = '';
    var background_color = '';
    var highlight_class = '';
	var search_icon = '';
	var search_text = '';

    $.fn.GSearch = function (options) {

        var default_values = {
            'highlight_class': 'highlight',
            'content_main': $('.com-content-article__body'),
            'background_color': 'yellow',
			'search_icon':'plugins/content/up/actions/page_search/loupe-64-red.png',
			'search_text':'Recherche',
        };

        $.extend(default_values, options);

        function private() {
            console.log("Private")
        };

        function init(obj) {
            content_main = default_values['content_main']
            options = default_values;
            element = $(obj)
            search_element = element.find('input');
            background_color = default_values['background_color'];
            highlight_class = default_values['highlight_class'];
            search_icon = default_values['search_icon'];
            search_text = default_values['search_text'];
        }

        function event_binding(obj) {
            $(obj).find('input').bind('keyup change', function () {
                removeHighlight(obj);
                var val = $(this).val();
                if (val) {
                    highlight(val);
                }
            });
            $('#gsearch_text_image').on("mouseenter", function () {
                $('#gsearch_text_field').val('');
                $('#gsearch_text_image').hide(800);
                $('#gsearch_text_field').show(800);
            });
            $('#gsearch_text_field').on("mouseout", function () {
                $('#gsearch_text_image').show(800);
                $('#gsearch_text_field').hide(800);
            });
        }

        function highlight(pat) {
            function innerHighlight(node, pat) {
                var skip = 0;
                if (node.nodeType == 3) {
                    var pos = node.data.toUpperCase().indexOf(pat);
                    if (pos >= 0) {
                        var spannode = document.createElement('span');
                        spannode.className = highlight_class;
                        spannode.style.backgroundColor = background_color;
                        var middlebit = node.splitText(pos);
                        var endbit = middlebit.splitText(pat.length);
                        var middleclone = middlebit.cloneNode(true);
                        spannode.appendChild(middleclone);
                        middlebit.parentNode.replaceChild(spannode, middlebit);
                        skip = 1;
                    }
                } else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
                    for (var i = 0; i < node.childNodes.length; ++i) {
                        i += innerHighlight(node.childNodes[i], pat);
                    }
                }
                return skip;
            }
            return content_main.each(function () {
                innerHighlight(this, pat.toUpperCase());
            });
        };

        function removeHighlight(obj) {
            function newNormalize(node) {
                for (var i = 0, children = node.childNodes, nodeCount = children.length; i < nodeCount; i++) {
                    var child = children[i];
                    if (child.nodeType == 1) {
                        newNormalize(child);
                        continue;
                    }
                    if (child.nodeType != 3) {
                        continue;
                    }
                    var next = child.nextSibling;
                    if (next == null || next.nodeType != 3) {
                        continue;
                    }
                    var combined_text = child.nodeValue + next.nodeValue;
                    var new_node = node.ownerDocument.createTextNode(combined_text);
                    node.insertBefore(new_node, child);
                    node.removeChild(child);
                    node.removeChild(next);
                    i--;
                    nodeCount--;
                }
            }
            return content_main.find("span.highlight").each(function () {
                var thisParent = this.parentNode;
                thisParent.replaceChild(this.firstChild, this);
                newNormalize(thisParent);
            }).end();
        };

        function generate_html(obj) {
            var html = '<div id="widget" class="sticky">' +
					   '<img src="' + search_icon + '"' +
					   ' class="gsearch_text_image" id="gsearch_text_image"' + 
					   ' style="display:block;width:40px;height:40px;">' + 
					   '<input type="textarea" name="gsearch_text_field" id="gsearch_text_field" class="gsearch_text_field"' +
						   ' style="display:none;width:300px;height:35px;" ' + 
								' placeholder="' + search_text +'" value="">' +
					   '</div>';

            $(obj).append(html);
        }

        function bindEvents(element) {
            init(element)
            generate_html(element);
            event_binding(element);
        };

        return this.each(function () {
            bindEvents(this);
        });
    };
})(jQuery);

