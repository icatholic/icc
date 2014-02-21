Ext.define('icc.common.SearchBar', {
	extend : 'Ext.toolbar.Toolbar',
	alias : 'widget.searchBar',
	requires : [ 'Ext.ux.form.SearchField','Ext.form.field.Trigger' ],
	initComponent : function() {
		if(this.store!=undefined) {
			Ext.apply(this, {
				items : [ {
					xtype : 'searchfield',
					hideLabel : true,
					fieldLabel : '搜索',
					labelWidth : 60,
					name : 'search',
					width : 100,
					store : this.store
				} ]
			});
		}
		this.callParent();
	}
});