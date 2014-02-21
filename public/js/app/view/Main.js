Ext.define('icc.view.Main', {
    extend: 'Ext.container.Container',
    requires:[
	'Ext.*',
	'Ext.chart.*',
        'Ext.form.field.HtmlEditor',
        'Ext.tab.Panel',
	'Ext.toolbar.Toolbar',
        'Ext.layout.container.Border',
        'icc.view.idatabase.Project.Grid',
        'icc.common.Form',
        'icc.common.Paging',
        'icc.common.Tbar',
        'icc.common.Window',
        'icc.common.SearchBar',
        'icc.common.Combobox',
        'Ext.ux.form.SearchField',
        'icc.view.idatabase.Data.Field.2dfield',
        'icc.view.idatabase.Data.Field.md5field',
        'icc.view.idatabase.Data.Field.sha1field',
        'icc.view.idatabase.Data.Field.boolfield'
    ],
    
    xtype: 'app-main',

    layout: {
        type: 'border'
    },

    items: [{
    	xtype : 'idatabaseProjectAccordion'
    },{
        xtype : 'idatabaseProjectTabPanel'
    }]
});