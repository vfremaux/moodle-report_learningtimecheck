YUI.add('moodle-report-learningtimecheck-reporttypechooser', function(Y) {
    var REPORTTYPECHOOSER = function() {
        REPORTTYPECHOOSER.superclass.constructor.apply(this, arguments);
    }

    Y.extend(REPORTTYPECHOOSER, Y.Base, {
        initializer : function(params) {
            if (params && params.formid) {
                var updatebut = Y.one('#'+params.formid+' #id_updatetype');
                var typeselect = Y.one('#'+params.formid+' #id_type');
                if (updatebut && typeselect) {
                    updatebut.setStyle('display', 'none');
                    typeselect.on('change', function() {
                        updatebut.simulate('click');
                    });
                }
            }
        }
    });

    M.report_learningtimecheck = M.report_learningtimecheck || {};
    M.report_learningtimecheck.init_reporttypechooser = function(params) {
        return new REPORTTYPECHOOSER(params);
    }
}, '@VERSION@', {requires:['base', 'node', 'node-event-simulate']});
