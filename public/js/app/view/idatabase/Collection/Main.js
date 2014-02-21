Ext.define('icc.view.idatabase.Collection.Main', {
	extend: 'Ext.panel.Panel',
	requires: ['Ext.layout.container.Border', 'icc.view.idatabase.Collection.Grid', 'icc.view.idatabase.Collection.TabPanel', 'icc.view.idatabase.Collection.Accordion'],
	alias: 'widget.idatabaseCollectionMain',
	closable: true,
	collapsible: true,
	layout: {
		type: 'border'
	},
	initComponent: function() {
		Ext.apply(this, {
			items: [{
				xtype: 'idatabaseCollectionAccordion',
				__PROJECT_ID__: this.__PROJECT_ID__,
				name: this.name,
				pluginItems: this.pluginItems
			}, {
				xtype: 'idatabaseCollectionTabPanel',
				__PROJECT_ID__: this.__PROJECT_ID__
			}]
		});
		this.callParent(arguments);
	}
});