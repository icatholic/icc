Ext.define('icc.view.idatabase.Import.Window', {
    extend: 'icc.common.Window',
    alias: 'widget.idatabaseImportWindow',
    title: '数据导入',
    initComponent: function() {
        Ext.apply(this, {
            items: [{
                xtype: 'iform',
                url: '/idatabase/import/import',
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
                    xtype: 'filefield',
                    name: 'import',
                    fieldLabel: '导入文件(*.xlsx)',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    name: 'sheetName',
                    fieldLabel: 'Sheet名称',
                    allowBlank: false,
                    value: 'Sheet1'
                }]
            }]
        });
        this.callParent();
    }
});