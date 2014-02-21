Ext.define('icc.view.idatabase.Collection.TabPanel', {
	extend: 'Ext.tab.Panel',
	alias: 'widget.idatabaseCollectionTabPanel',
	frame: true,
	region: 'center',
	resizeTabs: false,
	minTabwidth: 100,
	tabwidth: 100,
	enableTabScroll: true,
	initComponent: function() {
		Ext.apply(this, {
			items: [{
				xtype: 'idatabaseCollectionDashboard',
				title: '项目总览',
				__PROJECT_ID__: this.__PROJECT_ID__
			}]
		});
		this.callParent(arguments);
	}
});