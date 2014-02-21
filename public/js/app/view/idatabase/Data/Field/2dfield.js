Ext.define('icc.view.idatabase.Data.Field.2dfield', {
	extend : 'Ext.form.FieldSet',
	alias : 'widget.2dfield',
	title : '2D地理位置输入框',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'numberfield',
				name : this.fieldName + '[lng]',
				fieldLabel : '经度',
				allowBlank : true,
				decimalPrecision : 8
			}, {
				xtype : 'numberfield',
				name : this.fieldName + '[lat]',
				fieldLabel : '纬度',
				allowBlank : true,
				decimalPrecision : 8
			} ]
		});
		this.callParent();
	}
});