Ext.define('icc.view.idatabase.Mapping.Window', {
    extend: 'icc.common.Window',
    alias: 'widget.idatabaseMappingWindow',
    title: '映射管理',
    initComponent: function() {

        var activeRadioGrop = {
            xtype: 'radiogroup',
            fieldLabel: '是否启用物理映射',
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
                url: '/idatabase/mapping/update',
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
                    name: 'collection',
                    fieldLabel: '物理集合名称',
                    allowBlank: false,
                    value: this.collection
                }, {
                    xtype: 'textfield',
                    name: 'database',
                    fieldLabel: '物理数据库名称',
                    allowBlank: false,
                    value: this.database
                }, {
                    xtype: 'textfield',
                    name: 'cluster',
                    fieldLabel: '物理集群名称',
                    allowBlank: false,
                    value: this.cluster
                },
                activeRadioGrop]
            }]
        });
        this.callParent();
    }
});