Ext.define('icc.view.idatabase.Data.Add', {
	extend: 'icc.common.Window',
	alias: 'widget.idatabaseDataAdd',
	title: '添加数据',
	initComponent: function() {
		var items = Ext.Array.merge(this.addOrEditFields, [{
			xtype: 'hiddenfield',
			name: '__PROJECT_ID__',
			value: this.__PROJECT_ID__,
			allowBlank: false
		}, {
			xtype: 'hiddenfield',
			name: '__COLLECTION_ID__',
			value: this.__COLLECTION_ID__,
			allowBlank: false
		}, {
			xtype: 'hiddenfield',
			name: '__PLUGIN_ID__',
			value: this.__PLUGIN_ID__,
			allowBlank: false
		}]);

		Ext.apply(this, {
			items: [{
				xtype: 'iform',
				url: '/idatabase/data/add',
				items: items
			}]
		});

		this.callParent();
	}
});