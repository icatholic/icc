Ext.define('icc.view.idatabase.Index.Window', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseIndexWindow',
	title : '索引管理',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'idatabaseIndexGrid',
				__PROJECT_ID__ : this.__PROJECT_ID__,
				__COLLECTION_ID__ : this.__COLLECTION_ID__,
				plugin : this.plugin,
				__PLUGIN_ID__ : this.__PLUGIN_ID__,
				__PLUGIN_COLLECTION_ID__ : this.__PLUGIN_COLLECTION_ID__
			} ]
		});

		this.callParent();
	}

});