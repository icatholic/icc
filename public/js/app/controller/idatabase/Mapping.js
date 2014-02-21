Ext.define('icc.controller.idatabase.Mapping', {
    extend: 'Ext.app.Controller',
    models: [],
    stores: [],
    views: ['idatabase.Mapping.Window'],
    controllerName: 'idatabaseMapping',
    actions: {
        add: '/idatabase/mapping/add',
        edit: '/idatabase/mapping/edit',
        remove: '/idatabase/mapping/remove',
        save: '/idatabase/mapping/save'
    },
    refs: [{
        ref: 'tabPanel',
        selector: 'idatabaseProjectTabPanel'
    }],
    init: function() {
        var me = this;
        var controllerName = me.controllerName;

        if (controllerName == '') {
            Ext.Msg.alert('成功提示', '请设定controllerName');
            return false;
        }

        var listeners = {};

        listeners[controllerName + 'Window button[action=submit]'] = {
            click: function(button) {
                var form = button.up('form').getForm();
                if (form.isValid()) {
                    form.submit({
                        waitTitle: '系统提示',
                        waitMsg: '系统处理中，请稍后……',
                        success: function(form, action) {
                            Ext.Msg.alert('成功提示', action.result.msg);
                        },
                        failure: function(form, action) {
                            Ext.Msg.alert('失败提示', action.result.msg);
                        }
                    });
                } else {
                    Ext.Msg.alert('失败提示', '表单验证失败，请确认你填写的表单符合要求');
                }
            }
        };

        me.control(listeners);
        return true;
    }
});