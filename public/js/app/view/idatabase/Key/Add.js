Ext.define('icc.view.idatabase.Key.Add', {
	extend: 'icc.common.Window',
	alias: 'widget.idatabaseKeyAdd',
	title: '添加密钥',
	initComponent: function() {
		Ext.apply(this, {
			items: [{
				xtype: 'iform',
				url: '/idatabase/key/add',
				items: [{
					xtype: 'hiddenfield',
					name: '__PROJECT_ID__',
					fieldLabel: '项目编号',
					allowBlank: false,
					value: this.__PROJECT_ID__
				}, {
					name: 'name',
					fieldLabel: '密钥名称',
					allowBlank: false
				}, {
					name: 'desc',
					fieldLabel: '密钥描述',
					allowBlank: false
				}, {
					name: 'key',
					fieldLabel: '密钥',
					allowBlank: false
				}, {
					xtype: 'datefield',
					name: 'expire',
					fieldLabel: '过期时间',
					allowBlank: false,
					format: 'Y-m-d H:i:s'
				}, {
					xtype: 'radiogroup',
					fieldLabel: '默认？',
					defaultType: 'radiofield',
					layout: 'hbox',
					items: [{
						boxLabel: '是',
						name: 'default',
						inputValue: true
					}, {
						boxLabel: '否',
						name: 'default',
						inputValue: false,
						checked: true
					}]
				}, {
					xtype: 'radiogroup',
					fieldLabel: '有效?',
					defaultType: 'radiofield',
					layout: 'hbox',
					items: [{
						boxLabel: '有效',
						name: 'active',
						inputValue: true,
						checked: true
					}, {
						boxLabel: '失效',
						name: 'active',
						inputValue: false
					}]
				}]
			}]
		});

		this.callParent();
	}

});