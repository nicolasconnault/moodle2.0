/**
 * Toggles the selection checkboxes of all grade items children of the given eid (a category id)
 */
function togglecheckboxes(eid, value) {
    var rows = YAHOO.util.Dom.getElementsByClassName(eid);

    for (var i = 0; i < rows.length; i++) {
        var element = new YAHOO.util.Element(rows[i]);
        var checkboxes = element.getElementsByClassName('itemselect');
        if (checkboxes[0]) {
            checkboxes[0].checked=value;
        }
    }

    toggleCategorySelector();

}

function toggle_advanced_columns() {
    var advEls = YAHOO.util.Dom.getElementsByClassName("advanced");
    var shownAdvEls = YAHOO.util.Dom.getElementsByClassName("advancedshown");

    for (var i = 0; i < advEls.length; i++) {
        YAHOO.util.Dom.replaceClass(advEls[i], "advanced", "advancedshown");
    }

    for (var i = 0; i < shownAdvEls.length; i++) {
        YAHOO.util.Dom.replaceClass(shownAdvEls[i], "advancedshown", "advanced");
    }
}

/**
 * Check if any of the grade item checkboxes is ticked. If yes, enable the dropdown. Otherwise, disable it
 */
function toggleCategorySelector() {
    var itemboxes = YAHOO.util.Dom.getElementsByClassName('itemselect');
    for (var i = 0; i < itemboxes.length; i++) {
        if (itemboxes[i].checked) {
            document.getElementById('menumoveafter').disabled = false;
            return true;
    }
    document.getElementById('menumoveafter').disabled = 'disabled';
}

YAHOO.namespace('grade_edit_tree');

(function() {
    var Dom = YAHOO.util.Dom;
    var DDM = YAHOO.util.DragDropMgr;
    var Event = YAHOO.util.Event;
    var gretree = YAHOO.grade_edit_tree;

    gretree.DDApp = {

        init: function() {

            var edit_tree_table = Dom.get('grade_edit_tree_table');
            var i;
            var item_rows = edit_tree_table.getElementsByClassName('item', 'tr');
            var category_rows = edit_tree_table.getElementsByClassName('category', 'tr');

            new YAHOO.util.DDTarget('grade_edit_tree_table');

            for (i = 0; i < item_rows.length; i++) {
                if (!Dom.hasClass(item_rows[i],'categoryitem')) {
                    new gretree.DDList(item_rows[i]);
                }
            }

            for (i = 0; i < category_rows.length; i++) {
                if (!Dom.hasClass(category_rows[i],'coursecategory')) {
                    // Find the cell that spans rows for this category
                    var rowspancell = category_rows[i].getElementsByClassName('name', 'td');
                    var rowspan = parseInt(rowspancell[0].previousSibling.rowSpan) + 1;
                    var rows = Array(rowspan);
                    var lastRow = category_rows[i];

                    for (var j = 0; j < rowspan; j++) {
                        rows[j] = lastRow;
                        lastRow = lastRow.nextSibling;
                    }

                    new gretree.DDList(rows);
                }
            }

            YAHOO.util.Event.on("showButton", "click", this.showOrder);
            YAHOO.util.Event.on("switchButton", "click", this.switchStyles);
        },

        showOrder: function() {
            var parseTable = function(table, title) {
                var items = table.getElementsByTagName('tr');
                var out = title + ": ";

                for (i = 0; i < items.length; i++) {
                    out += items[i].id + ' ';
                }
                return out;
            };

            var table = Dom.get('grade_edit_tree_table');
            alert(parseTable(table, "Grade edit tree table"));
        },

        switchStyles: function() {
            Dom.get('grade_edit_tree_table').className = 'draglist_alt';
        }
    };

    gretree.DDList = function(id, sGroup, config) {

        gretree.DDList.superclass.constructor.call(this, id, sGroup, config);
        this.logger =  this.logger || YAHOO;
        var el = this.getDragEl();
        Dom.setStyle(el, 'opacity', 0.67);

        this.goingUp = false;
        this.lastY = 0;
    };

    YAHOO.extend(gretree.DDList, YAHOO.util.DDProxy, {

        startDrag: function(x, y) {
            this.logger.log(this.id + ' startDrag');

            // Make the proxy look like the source element
            var dragEl = this.getDragEl();
            var clickEl = this.getEl();

            Dom.setStyle(clickEl, 'visibility', 'hidden');

            dragEl.innerHTML = clickEl.innerHTML;

            Dom.setStyle(dragEl, 'color', Dom.getStyle(clickEl, 'color'));
            Dom.setStyle(dragEl, 'backgroundColor', Dom.getStyle(clickEl, 'backgroundColor'));
            Dom.setStyle(dragEl, 'border', '2px solid gray');
        },

        endDrag: function(e) {
            this.logger.log(this.id + ' endDrag');
            var srcEl = this.getEl();
            var proxy = this.getDragEl();

            // Show the proxy element and adnimate it to the src element's location
            Dom.setStyle(proxy, 'visibility', '');
            var a = new YAHOO.util.Motion(proxy, { points: { to: Dom.getXY(srcEl) } }, 0.2, YAHOO.util.Easing.easeOut);
            var proxyid = proxy.id;
            var thisid = this.id;

            // Hide the proxy and show the source element when finished with the animation
            a.onComplete.subscribe(function() {
                Dom.setStyle(proxyid, 'visibility', 'hidden');
                Dom.setStyle(thisid, 'visibility', '');
            });

            a.animate();
        },

        onDragDrop: function(e, id) {
            this.logger.log(this.id + ' dragDrop');

            // If there is one drop interaction, the tr was dropped either on the table, or it was dropped on the current location of the source element

            if (DDM.interactionInfo.drop.length === 1) {
                // The position of the cursor at the time of the drop (YAHOO.util.Point)
                var pt = DDM.interactionInfo.point;

                // The region occupied by the source element at the time of the drop
                var region = DDM.interactionInfo.sourceRegion;

                // Check to see if we are over the source element's location. We will append to the bottom of the list once we are sure it was a drop in the negative space
                if (!region.intersect(pt)) {
                    var destEl = Dom.get(id);
                    var destDD = DDM.getDDById(id);
                    destEl.appendChild(this.getEl());
                    destDD.isEmpty = false;
                    DDM.refreshCache();
                }
            }
        },

        onDrag: function(e) {

            // Keep track of the direction of the drag for use during onDragOver
            var y = Event.getPageY(e);

            if (y < this.lastY) {
                this.goingUp = true;
            } else if (y > this.lastY) {
                this.goingUp = false;
            }

            this.lastY = y;
        },

        onDragOver: function(e, id) {
            var srcEl = this.getEl();
            var destEl = Dom.get(id);

            // We are only concerned with tr items, we ignore the dragover notifications for the table
            if (destEl.nodeName.toLowerCase() == 'tr') {
                var orig_p = srcEl.parentNode;
                var p = destEl.parentNode;

                if (this.goingup) {
                    p.insertBefore(srcEl, destEl); // insert above
                } else {
                    p.insertBefore(srcEl, destEl.nextSibling); // insert below
                }

                DDM.refreshCache();
            }
        }
    });
    // YAHOO.util.Event.onDOMReady(gretree.DDApp.init, gretree.DDApp, true); // Uncomment this line when dragdrop is fully implemented
})();
