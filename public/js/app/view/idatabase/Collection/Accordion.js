Ext.define('icc.view.idatabase.Collection.Accordion', {
	extend : 'Ext.panel.Panel',
	xtype : 'idatabaseCollectionAccordion',
	region : 'west',
	layout : 'accordion',
	width : 400,
	title : '数据与插件',
	resizable : false,
	collapsible : true,
	autoScroll : true,
	pluginItems : [],
	initComponent : function() {
		var items = [ {
			xtype : 'idatabaseCollectionGrid',
			__PROJECT_ID__ : this.__PROJECT_ID__,
			plugin : false,
			__PLUGIN_ID__ : '',
			minHeight : 400
		} ];

		items = Ext.Array.merge(items, this.pluginItems);
		Ext.apply(this, {
			items : items
		});

		this.callParent();
	}
});