Ext.define('icc.view.idatabase.Key.Grid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.idatabaseKeyGrid',
	requires: ['icc.common.Paging'],
	collapsible: false,
	closable: false,
	multiSelect: false,
	disableSelection: false,
	selType: 'rowmodel',
	plugins: [Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 2
	})],
	columns: [{
		text: '密钥名称',
		dataIndex: 'name',
		flex: 1,
		field: {
			xtype: 'textfield',
			allowBlank: false
		}
	}, {
		text: '密钥描述',
		dataIndex: 'desc',
		flex: 1,
		hidden: true,
		field: {
			xtype: 'textfield',
			allowBlank: false
		}
	}, {
		text: '项目编号',
		dataIndex: 'project_id',
		flex: 1,
		field: {
			xtype: 'textfield',
			allowBlank: false
		}
	}, {
		text: '密钥编号',
		dataIndex: '_id',
		flex: 1,
		field: {
			xtype: 'textfield',
			allowBlank: false
		}
	}, {
		text: '密钥',
		dataIndex: 'key',
		flex: 1,
		field: {
			xtype: 'textfield',
			allowBlank: false
		}

	}, {
		xtype: 'booleancolumn',
		text: '默认?',
		dataIndex: 'default',
		flex: 1,
		trueText: '√',
		falseText: '×',
		field: {
			xtype: 'commonComboboxBoolean'
		}
	}, {
		xtype: 'booleancolumn',
		text: '有效性',
		dataIndex: 'active',
		flex: 1,
		trueText: '√',
		falseText: '×',
		field: {
			xtype: 'commonComboboxBoolean'
		}
	}, {
		xtype: 'datecolumn',
		text: '过期时间',
		dataIndex: 'expire',
		format: 'Y-m-d H:i:s',
		flex: 1,
		field: {
			xtype: 'datefield',
			allowBlank: false,
			format: 'Y-m-d H:i:s'
		}
	}, {
		xtype: 'datecolumn',
		text: '创建时间',
		dataIndex: '__CREATE_TIME__',
		flex: 1,
		format: 'Y-m-d',
		hidden: true
	}],
	initComponent: function() {
		var me = this;
		var store = Ext.create('icc.store.idatabase.Key');
		store['proxy']['extraParams']['__PROJECT_ID__'] = me.__PROJECT_ID__;
		store.load();

		Ext.apply(me, {
			store: store,
			bbar: {
				xtype: 'paging',
				store: store
			},
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					text: '操作',
					iconCls: 'menu',
					width: 100,
					menu: {
						xtype: 'menu',
						plain: true,
						items: [{
							xtype: 'button',
							text: '新增',
							iconCls: 'add',
							action: 'add'
						}, {
							xtype: 'button',
							text: '编辑',
							iconCls: 'edit',
							action: 'edit'
						}, {
							xtype: 'button',
							text: '保存',
							iconCls: 'save',
							action: 'save'
						}, {
							xtype: 'button',
							text: '删除',
							iconCls: 'remove',
							action: 'remove'
						}]
					}
				}, '-',
				{
					text: '管理',
					iconCls: 'menu',
					width: 100,
					menu: {
						xtype: 'menu',
						plain: true,
						items: [{
							xtype: 'button',
							text: '权限设置',
							iconCls: 'permission',
							action: 'permission'
						}]
					}
				}]
			}]
		});

		me.callParent();
	}

});