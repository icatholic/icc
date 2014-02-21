Ext.define('icc.view.idatabase.Lock.Window', {
    extend: 'icc.common.Window',
    alias: 'widget.idatabaseLockWindow',
    title: '安全管理',
    initComponent: function() {

        var activeRadioGrop = {
            xtype: 'radiogroup',
            fieldLabel: '是否启用安全密码',
            defaultType: 'radiofield',
            layout: 'hbox',
            items: [{
                boxLabel: '是',
                name: 'active',
                inputValue: true,
                checked: this.active ? true : false
            }, {
                boxLabel: '否',
                name: 'active',
                inputValue: false,
                checked: this.active ? false : true
            }]
        };

        Ext.apply(this, {
            items: [{
                xtype: 'iform',
                url: '/idatabase/lock/update',
                fieldDefaults: {
                    labelAlign: 'left',
                    labelWidth: 150,
                    anchor: '100%'
                },
                items: [{
                    xtype: 'hiddenfield',
                    name: '__PROJECT_ID__',
                    fieldLabel: '项目编号',
                    allowBlank: false,
                    value: this.__PROJECT_ID__
                }, {
                    xtype: 'hiddenfield',
                    name: '__COLLECTION_ID__',
                    fieldLabel: '集合编号',
                    allowBlank: false,
                    value: this.__COLLECTION_ID__
                }, {
                    xtype: 'textfield',
                    name: 'oldPassword',
                    fieldLabel: '原密码',
                    allowBlank: true,
                    inputType: 'password'
                }, {
                    xtype: 'textfield',
                    name: 'password',
                    fieldLabel: '新密码',
                    allowBlank: false,
                    inputType: 'password'
                }, {
                    xtype: 'textfield',
                    name: 'repeatPassword',
                    fieldLabel: '确认新密码',
                    allowBlank: false,
                    inputType: 'password'
                },
                activeRadioGrop]
            }]
        });
        this.callParent();
    }
});