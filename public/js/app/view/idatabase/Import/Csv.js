Ext.define('icc.view.idatabase.Import.Csv', {
    extend: 'icc.common.Window',
    alias: 'widget.idatabaseImportCsv',
    title: 'Csv数据导入',
    initComponent: function() {
        Ext.apply(this, {
            items: [{
                xtype: 'iform',
                url: '/idatabase/import/import-csv-job',
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
                    name: '__COLLECTION_NAME__',
                    fieldLabel: '集合名称',
                    allowBlank: false,
                    readOnly : true,
                    value: this.__COLLECTION_NAME__
                }, {
                    xtype: 'filefield',
                    name: 'import',
                    fieldLabel: '导入文件(*.csv)',
                    allowBlank: false
                }, {
                    xtype: 'radiogroup',
                    fieldLabel: '是否清除原有数据',
                    defaultType: 'radiofield',
                    layout: 'hbox',
                    items: [{
                        boxLabel: '是',
                        name: 'physicalDrop',
                        inputValue: true
                    }, {
                        boxLabel: '否',
                        name: 'physicalDrop',
                        inputValue: false,
                        checked: true
                    }]
                }]
            }]
        });
        this.callParent();
    }
});