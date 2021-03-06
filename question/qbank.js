// This script is included by question_bank_view and other parts of question/editlib.php.

// JavaScript belonging to question_bank_view.
question_bank = {
    strselectall: '',
    strdeselectall: '',
    headercheckbox: null,
    firstcheckbox: null,

    init_checkbox_column: function(strselectall, strdeselectall, firstcbid) {
        question_bank.strselectall = strselectall;
        question_bank.strdeselectall = strdeselectall;

        // Find the header checkbox, and initialise it.
        question_bank.headercheckbox = document.getElementById('qbheadercheckbox');
        question_bank.headercheckbox.disabled = false;
        question_bank.headercheckbox.title = strselectall;

        // Find the first real checkbox.
        question_bank.firstcheckbox = document.getElementById(firstcbid);

        // Add the event handler.
        YAHOO.util.Event.addListener(question_bank.headercheckbox, 'change', question_bank.header_checkbox_click);
    },

    header_checkbox_click: function() {
        if (question_bank.firstcheckbox.checked) {
            deselect_all_in('TABLE', null, 'categoryquestions');
            question_bank.headercheckbox.title = question_bank.strselectall;
        } else {
            select_all_in('TABLE', null, 'categoryquestions');
            question_bank.headercheckbox.title = question_bank.strdeselectall;
        }
        question_bank.headercheckbox.checked = false;
    }
};

// JavaScript to make the list of question types pop-up when you click an add
// add question button.
qtype_chooser = {
    radiobuttons: [],
    labels: [],
    container: null,
    submitbutton: null,

    init: function(boxid) {
        // Find the radio buttons.
        qtype_chooser.radiobuttons = YAHOO.util.Dom.getElementsBy(
                function(el) { return el.type == 'radio'; }, 'input' , boxid);
        qtype_chooser.labels = YAHOO.util.Dom.getElementsByClassName('qtypeoption', 'div', boxid);

        // Find the submit button.
        qtype_chooser.submitbutton = document.getElementById(boxid + '_submit');
        qtype_chooser.enable_disable_submit();

        // Add the event handlers.
        YAHOO.util.Event.addListener(boxid, 'click', qtype_chooser.enable_disable_submit);
        YAHOO.util.Event.addListener(boxid, 'key_down', qtype_chooser.enable_disable_submit);
        YAHOO.util.Event.addListener(boxid, 'key_up', qtype_chooser.enable_disable_submit);
        YAHOO.util.Event.addListener(boxid, 'dblclick', qtype_chooser.double_click);

        YAHOO.util.Event.onDOMReady(qtype_chooser.init_container);
    },

    enable_disable_submit: function() {
        var ok = false;
        for (var i = 0; i < qtype_chooser.radiobuttons.length; i++) {
            if (qtype_chooser.radiobuttons[i].checked) {
                ok = true;
                YAHOO.util.Dom.addClass(qtype_chooser.labels[i], 'selected');
            } else {
                YAHOO.util.Dom.removeClass(qtype_chooser.labels[i], 'selected');
            }
        }
        qtype_chooser.submitbutton.disabled = !ok;
    },

    double_click: function() {
        if (!qtype_chooser.submitbutton.disabled) {
            qtype_chooser.submitbutton.form.submit();
        }
    },

    init_container: function() {
        if (!document.getElementById('qtypechoicecontainer')) {
            return;
        }
        var qtypechoicecontainer = document.getElementById('qtypechoicecontainer');
        qtypechoicecontainer.parentNode.removeChild(qtypechoicecontainer);
        document.body.appendChild(qtypechoicecontainer);
        qtype_chooser.container = new YAHOO.widget.Dialog(qtypechoicecontainer, {
            constraintoviewport: true,
            visible: false,
            modal: true,
            fixedcenter: true,
            close: true,
            draggable: true,
            dragOnly: true,
            postmethod: 'form',
            zIndex: 1000
        });
        qtype_chooser.container.render();

        YAHOO.util.Event.addListener('chooseqtypecancel', 'click', qtype_chooser.cancel_popup);

        var addforms = YAHOO.util.Dom.getElementsBy(function(el) {
                return /question\/addquestion\.php/.test(el.action); }, 'form', document.body);
        for (var i = 0; i < addforms.length; i++) {
            YAHOO.util.Event.addListener(addforms[i], 'submit', qtype_chooser.add_button_click);
        }
    },

    add_button_click: function(e) {
        var form = document.getElementById('qtypeformdiv');

        var oldhidden = YAHOO.util.Dom.getElementsBy(
                function(el) { return el.type == 'hidden'; }, 'input', form);
        for (var i = 0; i < oldhidden.length; i++) {
            oldhidden[i].parentNode.removeChild(oldhidden[i]);
        }

        var wantedhidden = YAHOO.util.Dom.getElementsBy(
                function(el) { return el.type == 'hidden'; }, 'input', this);
        for (i = 0; i < wantedhidden.length; i++) {
            form.appendChild(wantedhidden[i].cloneNode(true));
        }

        qtype_chooser.container.show();
        YAHOO.util.Event.preventDefault(e);
    },

    cancel_popup: function(e) {
        qtype_chooser.container.hide();
        YAHOO.util.Event.preventDefault(e);
    }
};