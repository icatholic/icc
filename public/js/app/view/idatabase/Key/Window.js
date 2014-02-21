Ext.define('icc.view.idatabase.Key.Window', {
	extend: 'icc.common.Window',
	alias: 'widget.idatabaseKeyWindow',
	title: '项目密钥管理',
	initComponent: function() {
		Ext.apply(this, {
			items: [{
				xtype: 'idatabaseKeyGrid',
				__PROJECT_ID__: this.__PROJECT_ID__
			}]
		});
		this.callParent();
	}

});