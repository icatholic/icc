Ext.define('icc.controller.idatabase.Statistic', {
    extend: 'Ext.app.Controller',
    models: ['idatabase.Statistic'],
    stores: ['idatabase.Statistic.Period', 'idatabase.Statistic.Type', 'idatabase.Statistic', 'idatabase.Statistic.One', 'idatabase.Statistic.Series', 'idatabase.Statistic.Axis', 'idatabase.Statistic.Method'],
    views: ['idatabase.Statistic.Combobox.Period', 'idatabase.Statistic.Combobox.Type', 'idatabase.Statistic.Combobox.Series', 'idatabase.Statistic.Combobox.Axis', 'idatabase.Statistic.Combobox.Method', 'idatabase.Statistic.Combobox.Axis', 'idatabase.Statistic.Window', 'idatabase.Statistic.Grid', 'idatabase.Statistic.Add', 'idatabase.Statistic.Edit', 'idatabase.Statistic.Chart'],
    controllerName: 'idatabaseStatistic',
    actions: {
        add: '/idatabase/statistic/add',
        edit: '/idatabase/statistic/edit',
        remove: '/idatabase/statistic/remove',
        save: '/idatabase/statistic/save'
    },
    refs: [{
        ref: 'tabPanel',
        selector: 'idatabaseProjectTabPanel'
    }, {
        ref: 'projectGrid',
        selector: 'idatabaseProjectGrid'
    }, {
        ref: 'projectAccordion',
        selector: 'idatabaseProjectAccordion'
    }],
    getExpandedAccordion: function() {
        return this.getProjectAccordion().child("[collapsed=false]");
    },
    init: function() {
        var me = this;
        var controllerName = me.controllerName;

        if (controllerName == '') {
            Ext.Msg.alert('成功提示', '请设定controllerName');
            return false;
        }

        me.addRef([{
            ref: 'list',
            selector: me.controllerName + 'Grid'
        }, {
            ref: 'add',
            selector: me.controllerName + 'Add'
        }, {
            ref: 'edit',
            selector: me.controllerName + 'Edit'
        }]);

        var listeners = {};

        listeners[controllerName + 'Add button[action=submit]'] = {
            click: function(button) {
                var store = me.getList().store;
                var form = button.up('form').getForm();
                if (form.isValid()) {
                    form.submit({
                        waitTitle: '系统提示',
                        waitMsg: '系统处理中，请稍后……',
                        success: function(form, action) {
                            Ext.Msg.alert('成功提示', action.result.msg);
                            form.reset();
                            store.load();
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

        listeners[controllerName + 'Edit button[action=submit]'] = {
            click: function(button) {
                var store = me.getList().store;
                var form = button.up('form').getForm();
                if (form.isValid()) {
                    form.submit({
                        waitTitle: '系统提示',
                        waitMsg: '系统处理中，请稍后……',
                        success: function(form, action) {
                            Ext.Msg.alert('成功提示', action.result.msg);
                            store.load();
                        },
                        failure: function(form, action) {
                            Ext.Msg.alert('失败提示', action.result.msg);
                        }
                    });
                }
            }
        };

        listeners[controllerName + 'Grid button[action=add]'] = {
            click: function(button) {
                var grid = button.up('gridpanel');
                var win = Ext.widget(controllerName + 'Add', {
                    __PROJECT_ID__: grid.__PROJECT_ID__,
                    __COLLECTION_ID__: grid.__COLLECTION_ID__
                });
                win.show();
            }
        };

        listeners[controllerName + 'Grid button[action=edit]'] = {
            click: function(button) {
                var grid = button.up('gridpanel');
                var selections = grid.getSelectionModel().getSelection();
                if (selections.length > 0) {
                    var win = Ext.widget(controllerName + 'Edit', {
                        __PROJECT_ID__: grid.__PROJECT_ID__,
                        __COLLECTION_ID__: grid.__COLLECTION_ID__
                    });
                    var form = win.down('form').getForm();
                    form.loadRecord(selections[0]);
                    win.show();
                } else {
                    Ext.Msg.alert('提示信息', '请选择你要编辑的项');
                }
            }
        };

        listeners[controllerName + 'Grid button[action=save]'] = {
            click: function(button) {
                var records = me.getExpandedAccordion().store.getUpdatedRecords();
                var recordsNumber = records.length;
                if (recordsNumber == 0) {
                    Ext.Msg.alert('提示信息', '很遗憾，未发现任何被修改的信息需要保存');
                }
                var updateList = [];
                for (var i = 0; i < recordsNumber; i++) {
                    record = records[i];
                    updateList.push(record.data);
                }

                Ext.Ajax.request({
                    url: me.actions.save,
                    params: {
                        updateInfos: Ext.encode(updateList),
                        __PROJECT_ID__: grid.__PROJECT_ID__,
                        __COLLECTION_ID__: grid.__COLLECTION_ID__
                    },
                    scope: me,
                    success: function(response) {
                        var text = response.responseText;
                        var json = Ext.decode(text);
                        Ext.Msg.alert('提示信息', json.msg);
                        if (json.success) {
                            me.getExpandedAccordion().store.load();
                        }
                    }
                });

            }
        };

        listeners[controllerName + 'Grid button[action=remove]'] = {
            click: function(button) {
                var grid = button.up('gridpanel');
                var selections = grid.getSelectionModel().getSelection();
                if (selections.length > 0) {
                    Ext.Msg.confirm('提示信息', '请确认是否要删除您选择的信息?', function(btn) {
                        if (btn == 'yes') {
                            var _id = [];
                            for (var i = 0; i < selections.length; i++) {
                                selection = selections[i];
                                grid.store.remove(selection);
                                _id.push(selection.get('_id'));
                            }

                            Ext.Ajax.request({
                                url: me.actions.remove,
                                params: {
                                    _id: Ext.encode(_id),
                                    __PROJECT_ID__: grid.__PROJECT_ID__,
                                    __COLLECTION_ID__: grid.__COLLECTION_ID__
                                },
                                scope: me,
                                success: function(response) {
                                    var text = response.responseText;
                                    var json = Ext.decode(text);
                                    Ext.Msg.alert('提示信息', json.msg);
                                    if (json.success) {
                                        grid.store.load();
                                    }
                                }
                            });
                        }
                    }, me);
                } else {
                    Ext.Msg.alert('提示信息', '请选择您要删除的项');
                }
            }
        };

        listeners['idatabaseStatisticChart button[action=statisticExport]'] = {
            click: function(button) {
                var win = button.up('window');
                var statistics = win.__STATISTIC_INFO__;
                var extraParams = win.__EXTRAPARAMS__;
                var statistics_id = statistics.get('_id');
                var params = {
                    'action': 'statistic',
                    'export': true,
                    '__STATISTIC_ID__': statistics_id,
                    '__PROJECT_ID__': win.__PROJECT_ID__,
                    '__COLLECTION_ID__': win.__COLLECTION_ID__
                };

                params = Ext.Object.merge(params, win.__EXTRAPARAMS__);
                window.location.href = '/idatabase/data/statistic?' + Ext.Object.toQueryString(params);
            }
        };


        me.control(listeners);
        return true;
    }
});