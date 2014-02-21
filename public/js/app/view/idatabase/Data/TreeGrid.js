Ext.define('icc.view.idatabase.Data.TreeGrid', {
	extend: 'Ext.tree.Panel',
	alias: 'widget.idatabaseDataTreeGrid',
	region: "center",
	border: false,
	collapsible: false,
	split: true,
	closable: false,
	disableSelection: false,
	sortableColumns: false,
	useArrows: true,
	rootVisible: false,
	multiSelect: true,
	singleExpand: false,
	selType: 'rowmodel',
	plugins: [Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit: 2
	})],
	initComponent: function() {
		Ext.apply(this, {
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					text: '新增',
					iconCls: 'add',
					width: 60,
					action: 'add'
				}, '-',
				{
					text: '编辑',
					iconCls: 'edit',
					width: 60,
					action: 'edit'
				}, '-',
				{
					text: '保存',
					iconCls: 'save',
					width: 60,
					action: 'save'
				}, '-',
				{
					text: '删除',
					iconCls: 'delete',
					width: 60,
					tooltip: '删除',
					action: 'remove'
				}, '->',
				{
					text: '清空',
					iconCls: 'recycle',
					width: 60,
					tooltip: '清空',
					action: 'drop'
				}]
			}],
			store: this.store
		});
		this.callParent();
	}
});