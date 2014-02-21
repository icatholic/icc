Ext.define('icc.view.idatabase.Plugin.System.Window', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginSystemWindow',
	title : '系统插件管理',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'idatabasePluginSystemGrid'
			} ]
		});

		this.callParent();
	}

});