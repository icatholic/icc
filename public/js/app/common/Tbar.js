Ext.define('icc.common.Tbar', {
	extend : 'Ext.toolbar.Toolbar',
	alias : 'widget.tbar',
	items : [ {
		text : '操作',
		iconCls : 'menu',
		width : 106,
		menu : {
			xtype : 'menu',
			plain: true,
			items : [ {
				xtype : 'button',
				text : '新增',
				iconCls : 'add',
				action : 'add'
			}, {
				xtype : 'button',
				text : '编辑',
				iconCls : 'edit',
				action : 'edit'
			}, {
				xtype : 'button',
				text : '保存',
				iconCls : 'save',
				action : 'save'
			}, {
				xtype : 'button',
				text : '删除',
				iconCls : 'remove',
				action : 'remove'
			} ]
		}
	}]
});