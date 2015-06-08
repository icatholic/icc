Ext.define('icc.view.idatabase.Structure.Edit', {
	extend: 'icc.common.Window',
	alias: 'widget.idatabaseStructureEdit',
	title: '编辑属性',
	initComponent: function() {
		var items = [{
			xtype: 'hiddenfield',
			name: '_id',
			fieldLabel: '属性_id',
			allowBlank: false
		}, {
			xtype: 'hiddenfield',
			name: '__PROJECT_ID__',
			fieldLabel: '项目编号',
			allowBlank: false,
			value: this.__PROJECT_ID__
		}, {
			xtype: 'hiddenfield',
			name: '__COLLECTION_ID__',
			fieldLabel: '集合编号',
			allowBlank: false,
			value: this.__COLLECTION_ID__
		}, {
			xtype: 'hiddenfield',
			name: '__PLUGIN_ID__',
			fieldLabel: '插件编号',
			allowBlank: false,
			value: this.__PLUGIN_ID__
		}, {
			xtype: 'hiddenfield',
			name: '__PLUGIN_COLLECTION_ID__',
			fieldLabel: '插件集合编号',
			allowBlank: false,
			value: this.__PLUGIN_COLLECTION_ID__
		}, {
			name: 'field',
			fieldLabel: '属性名(英文数字)',
			allowBlank: false
		}, {
			name: 'label',
			fieldLabel: '属性描述',
			allowBlank: false
		}, {
			name:'emptyText',
			fieldLabel: '属性填写要求',
			allowBlank: true
		}, , {
			xtype: 'combobox',
			name: 'type',
			fieldLabel: '输入类型',
			allowBlank: false,
			store: 'idatabase.Structure.Type',
			valueField: 'val',
			displayField: 'name',
			editable: false
		}, {
			xtype: 'idatabaseStructureFilterCombobox'
		}, {
			xtype: 'radiogroup',
			fieldLabel: '是否为检索条件',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'searchable',
				inputValue: true,
				checked: true
			}, {
				boxLabel: '否',
				name: 'searchable',
				inputValue: false
			}]
		}, {
			xtype: 'radiogroup',
			fieldLabel: '是否在列表页显示',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'main',
				inputValue: true,
				checked: true
			}, {
				boxLabel: '否',
				name: 'main',
				inputValue: false
			}]
		}, {
			xtype: 'radiogroup',
			fieldLabel: '是否必填',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'required',
				inputValue: true
			}, {
				boxLabel: '否',
				name: 'required',
				inputValue: false,
				checked: true
			}]
		}, {
			xtype: 'radiogroup',
			fieldLabel: '是否唯一',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'unique',
				inputValue: true
			}, {
				boxLabel: '否',
				name: 'unique',
				inputValue: false,
				checked: true
			}]
		}, {
			xtype: 'radiogroup',
			fieldLabel: '作为导出字段',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'export',
				inputValue: true
			}, {
				boxLabel: '否',
				name: 'export',
				inputValue: false,
				checked: true
			}]
		}, {
			xtype: 'radiogroup',
			fieldLabel: '记录Tree的父节点',
			defaultType: 'radiofield',
			layout: 'hbox',
			items: [{
				boxLabel: '是',
				name: 'isFatherField',
				inputValue: true
			}, {
				boxLabel: '否',
				name: 'isFatherField',
				inputValue: false,
				checked: true
			}]
		}, {
			xtype: 'numberfield',
			name: 'orderBy',
			fieldLabel: '排序',
			allowBlank: false,
			value: this.orderBy
		}, {
			xtype: 'textareafield',
			name: 'xTemplate',
			fieldLabel: '模板设定(可选)',
			allowBlank: true
		}, {
			xtype: 'fieldset',
			title: '文件资源设定(可选)',
			collapsed: true,
			collapsible: true,
			items: [{
				xtype: 'radiogroup',
				fieldLabel: '是否在表格中显示图片',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'showImage',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'showImage',
					inputValue: false,
					checked: true
				}]
			}, {
				xtype: 'radiogroup',
				fieldLabel: '是否只输出文件_id',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'displayFileId',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'displayFileId',
					inputValue: false,
					checked: true
				}]
			}, {
				xtype: 'textfield',
				fieldLabel: '文件资源域名/CDN路径设定',
				name: 'cdnUrl',
				vtype: 'url',
				allowBlank: true
			}]
		}, {
			xtype: 'fieldset',
			title: '关联设定（选填）',
			collapsed: true,
			collapsible: true,
			items: [{
				xtype: 'idatabaseCollectionAllCombobox',
				__PROJECT_ID__: this.__PROJECT_ID__,
				fieldLabel: '关联集合列表',
				name: 'rshCollection'
			}, {
				xtype: 'radiogroup',
				fieldLabel: '是否开启多选功能',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'isBoxSelect',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'isBoxSelect',
					inputValue: false,
					checked: true
				}]
			}, {
				xtype: 'textareafield',
				fieldLabel: '关联集合约束条件',
				name: 'rshSearchCondition'
			}, {
				xtype: 'fieldset',
				title: '联动菜单（选填）',
				collapsed: true,
				collapsible: true,
				items: [{
					xtype: 'radiogroup',
					fieldLabel: '开启菜单联动功能',
					defaultType: 'radiofield',
					layout: 'hbox',
					items: [{
						boxLabel: '是',
						name: 'isLinkageMenu',
						inputValue: true
					}, {
						boxLabel: '否',
						name: 'isLinkageMenu',
						inputValue: false,
						checked: true
					}]
				}, {
					xtype: 'textfield',
					fieldLabel: '联动清空字段(多字段,分隔)',
					name: 'linkageClearValueField'
				}, {
					xtype: 'textfield',
					fieldLabel: '联动赋值字段(多字段,分隔)',
					name: 'linkageSetValueField'
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: '当本集合被关联时（选填）',
			collapsed: true,
			collapsible: true,
			items: [{
				xtype: 'radiogroup',
				fieldLabel: '作为关联表显示字段',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'rshKey',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'rshKey',
					inputValue: false,
					checked: true
				}]
			}, {
				xtype: 'radiogroup',
				fieldLabel: '作为关联表提交字段',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'rshValue',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'rshValue',
					inputValue: false,
					checked: true
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: '快捷录入（选填）',
			collapsed: true,
			collapsible: true,
			items: [{
				xtype: 'radiogroup',
				fieldLabel: '开启快捷录入',
				defaultType: 'radiofield',
				layout: 'hbox',
				items: [{
					boxLabel: '是',
					name: 'isQuick',
					inputValue: true
				}, {
					boxLabel: '否',
					name: 'isQuick',
					inputValue: false,
					checked: true
				}]
			}, {
				xtype: 'idatabaseCollectionCombobox',
				__PROJECT_ID__: this.__PROJECT_ID__,
				fieldLabel: '目标集合列表',
				name: 'quickTargetCollection'
			}]
		}];

		Ext.apply(this, {
			items: [{
				xtype: 'iform',
				url: '/idatabase/structure/edit',
				fieldDefaults: {
					labelAlign: 'left',
					labelWidth: 150,
					anchor: '100%'
				},
				items: items
			}]
		});

		this.callParent();
	}

});