Ext.define('icc.view.idatabase.Statistic.Grid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.idatabaseStatisticGrid',
	requires: ['icc.common.Paging', 'icc.view.common.Combobox.Boolean'],
	collapsible: false,
	closable: false,
	multiSelect: false,
	disableSelection: false,
	sortableColumns: false,
	initComponent: function() {
		var me = this;
		var store = Ext.create('icc.store.idatabase.Statistic');
		store.proxy.extraParams = {
			'__PROJECT_ID__': me.__PROJECT_ID__,
			'__COLLECTION_ID__': me.__COLLECTION_ID__,
			'__PLUGIN_ID__': me.__PLUGIN_ID__,
			'__PLUGIN_COLLECTION_ID__': me.__PLUGIN_COLLECTION_ID__
		};
		store.load();

		Ext.apply(me, {
			store: store,
			bbar: {
				xtype: 'paging',
				store: store
			},
			selType: 'rowmodel',
			plugins: [Ext.create('Ext.grid.plugin.CellEditing', {
				clicksToEdit: 2
			})],
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
				}]
			}],
			columns: [{
				text: '_id',
				dataIndex: '_id',
				hidden: true
			}, {
				text: '名称',
				dataIndex: 'name',
				flex: 1,
				field: {
					xtype: 'textfield',
					allowBlank: false
				}
			}, {
				text: '类型',
				dataIndex: 'seriesType',
				flex: 1,
				field: {
					xtype: 'combobox',
					store: 'idatabase.Statistic.Series',
					displayField: 'name',
					valueField: 'value',
					queryMode: 'local',
					pageSize: 0,
					editable: false,
					typeAhead: false
				},
				renderer: function(value) {
					var store = Ext.data.StoreManager.lookup('idatabase.Statistic.Series');
					var record = store.findRecord('value', value, 0, false, true, true);
					if (record != null) {
						return record.get('name');
					}
					return value;
				}
			}, {
				text: '执行间隔',
				dataIndex: 'interval',
				flex: 1,
				field: {
					xtype: 'numberfield'
				}
			}, {
				xtype: 'datecolumn',
				text: '更新时间',
				dataIndex: 'lastExecuteTime',
				flex: 1,
				format : 'Y-m-d H:i:s'
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '统计中……',
				dataIndex: 'isRunning',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'datecolumn',
				text: '创建时间',
				dataIndex: '__CREATE_TIME__',
				flex: 1,
				format: 'Y-m-d',
				hidden: true
			}]
		});

		me.callParent();
	}
});