Ext.define('icc.view.idatabase.Statistic.Window', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseStatisticWindow',
	title : '统计管理',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'idatabaseStatisticGrid',
				__PROJECT_ID__ : this.__PROJECT_ID__,
				__COLLECTION_ID__ : this.__COLLECTION_ID__,
				__PLUGIN_ID__ : this.__PLUGIN_ID__,
				__PLUGIN_COLLECTION_ID__ : this.__PLUGIN_COLLECTION_ID__
			} ]
		});

		this.callParent();
	}

});