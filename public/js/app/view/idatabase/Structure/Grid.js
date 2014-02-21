Ext.define('icc.view.idatabase.Structure.Grid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.idatabaseStructureGrid',
	requires: ['icc.common.Paging', 'icc.view.common.Combobox.Boolean'],
	collapsible: false,
	closable: false,
	multiSelect: false,
	disableSelection: false,
	sortableColumns: false,
	initComponent: function() {
		var me = this;
		var store = Ext.create('icc.store.idatabase.Structure');

		store.proxy.extraParams = {
			__PROJECT_ID__: me.__PROJECT_ID__,
			__COLLECTION_ID__: me.__COLLECTION_ID__,
			__PLUGIN_ID__: me.__PLUGIN_ID__,
			__PLUGIN_COLLECTION_ID__: me.__PLUGIN_COLLECTION_ID__
		};
		store.load();

		var collectionStore = Ext.create('icc.store.idatabase.Collection.All');
		collectionStore.proxy.extraParams = {
			__PROJECT_ID__: me.__PROJECT_ID__,
			action: 'all'
		};

		me.on({
			beforerender: function() {
				collectionStore.load(function(store, records, success) {
					if (success) {
						me.getView().refresh();
					}
				});
			}
		});

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
				text: '排序',
				dataIndex: 'orderBy',
				flex: 1,
				field: {
					xtype: 'numberfield'
				}
			}, {
				text: '名称',
				dataIndex: 'field',
				flex: 1,
				field: {
					xtype: 'textfield',
					allowBlank: false
				}
			}, {
				text: '描述',
				dataIndex: 'label',
				flex: 1,
				field: {
					xtype: 'textfield'
				}
			}, {
				text: '类型',
				dataIndex: 'type',
				flex: 2,
				field: {
					xtype: 'combobox',
					store: 'idatabase.Structure.Type',
					displayField: 'name',
					valueField: 'val',
					queryMode: 'local',
					pageSize: 0,
					editable: false,
					typeAhead: false
				},
				renderer: function(value) {
					var store = Ext.data.StoreManager.lookup('idatabase.Structure.Type');
					var record = store.findRecord('val', value, 0, false, true, true);
					if (record != null) {
						return record.get('name');
					}
					return value;
				}
			}, {
				text: '过滤器',
				dataIndex: 'filter',
				flex: 2,
				field: {
					xtype: 'combobox',
					store: 'idatabase.Structure.FilterType',
					displayField: 'name',
					valueField: 'val',
					queryMode: 'remote',
					pageSize: 0,
					editable: false,
					typeAhead: false
				},
				renderer: function(value) {
					var store = Ext.data.StoreManager.lookup('idatabase.Structure.FilterType');
					var record = store.findRecord('val', value);
					if (record != null) {
						return record.get('name');
					}
					return value;
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '检索?',
				dataIndex: 'searchable',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '列表?',
				dataIndex: 'main',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '必填?',
				dataIndex: 'required',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '图片?',
				dataIndex: 'showImage',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '父?',
				dataIndex: 'isFatherField',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				text: '关联集合',
				dataIndex: 'rshCollection',
				flex: 2,
				field: {
					xtype: 'combobox',
					store: collectionStore,
					displayField: 'name',
					valueField: 'alias'
				},
				renderer: function(value) {
					var record = collectionStore.findRecord('alias', value, 0, false, true, true);
					if (record != null) {
						return record.get('name');
					}
					return '';
				}
			}, {
				xtype: 'booleancolumn',
				trueText: '√',
				falseText: '×',
				text: '多选?',
				dataIndex: 'isBoxSelect',
				flex: 1,
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				text: '显示字段',
				dataIndex: 'rshKey',
				flex: 1,
				trueText: '√',
				falseText: '×',
				field: {
					xtype: 'commonComboboxBoolean'
				}
			}, {
				xtype: 'booleancolumn',
				text: '提交字段',
				dataIndex: 'rshValue',
				flex: 1,
				trueText: '√',
				falseText: '×',
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