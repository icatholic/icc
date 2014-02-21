Ext.define('icc.view.idatabase.Collection.Add', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseCollectionAdd',
	title : '添加数据集合',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/collection/add',
				items : [ {
					xtype : 'hiddenfield',
					name : '__PROJECT_ID__',
					value : this.__PROJECT_ID__,
					vtype : 'alphanum'
				}, {
					xtype : 'hiddenfield',
					name : 'plugin',
					value : this.plugin,
					allowBlank : false
				}, {
					xtype : 'hiddenfield',
					name : '__PLUGIN_ID__',
					value : this.__PLUGIN_ID__,
					vtype : 'alphanum',
					allowBlank : false
				}, {
					xtype : 'hiddenfield',
					name : '__PLUGIN_COLLECTION_ID__',
					value : '',
					vtype : 'alphanum',
					allowBlank : false
				}, {
					name : 'alias',
					fieldLabel : '集合别名(英文)',
					allowBlank : false,
					vtype : 'alphanum'
				}, {
					name : 'name',
					fieldLabel : '集合名称(中文)',
					allowBlank : false
				}, {
					xtype : 'textareafield',
					name : 'desc',
					fieldLabel : '功能描述',
					allowBlank : false
				}, {
					xtype : 'numberfield',
					name : 'orderBy',
					fieldLabel : '排列顺序',
					allowBlank : false,
					value : this.orderBy
				}, {
					xtype : 'fieldset',
					title : '高级设定(选填)',
					collapsed : true,
					collapsible : true,
					items : [ {
						xtype : 'radiogroup',
						fieldLabel : '是否专家集合',
						defaultType : 'radiofield',
						layout : 'hbox',
						items : [ {
							boxLabel : '是',
							name : 'isProfessional',
							inputValue : true
						}, {
							boxLabel : '否',
							name : 'isProfessional',
							inputValue : false,
							checked : true
						} ]
					}, {
						xtype : 'radiogroup',
						fieldLabel : '是否树状集合',
						defaultType : 'radiofield',
						layout : 'hbox',
						items : [ {
							boxLabel : '是',
							name : 'isTree',
							inputValue : true
						}, {
							boxLabel : '否',
							name : 'isTree',
							inputValue : false,
							checked : true
						} ]
					}, {
						xtype : 'fieldset',
						title : '触发iWebsite关联逻辑的URL(选填)',
						collapsed : true,
						collapsible : true,
						items : [ {
							xtype : 'radiogroup',
							fieldLabel : '是否自动执行',
							defaultType : 'radiofield',
							layout : 'hbox',
							items : [ {
								boxLabel : '是',
								name : 'isAutoHook',
								inputValue : true								
							}, {
								boxLabel : '否',
								name : 'isAutoHook',
								inputValue : false,
								checked : true
							} ]
						}, {
							xtype : 'textfield',
							name : 'hook',
							fieldLabel : 'Hook触发器',
							allowBlank : true,
							vtype : 'url'
						}, {
							xtype : 'textfield',
							name : 'hookKey',
							fieldLabel : 'Hook安全密钥(至少8位)',
							allowBlank : true,
							minLength : 8
						} ]
					}, {
						xtype : 'fieldset',
						title : '行展开模式设定（选填）',
						collapsed : true,
						collapsible : true,
						items : [ {
							xtype : 'radiogroup',
							fieldLabel : '是否行展开显示',
							defaultType : 'radiofield',
							layout : 'hbox',
							items : [ {
								boxLabel : '是',
								name : 'isRowExpander',
								inputValue : true
							}, {
								boxLabel : '否',
								name : 'isRowExpander',
								inputValue : false,
								checked : true
							} ]
						}, {
							xtype : 'textareafield',
							name : 'rowExpanderTpl',
							fieldLabel : '行展开模板(支持Ext.Xtemplate)',
							allowBlank : true
						} ]
					} ]
				} ]
			} ]
		});

		this.callParent();
	}

});