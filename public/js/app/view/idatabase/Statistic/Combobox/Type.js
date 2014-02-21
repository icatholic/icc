Ext.define('icc.view.idatabase.Statistic.Combobox.Type', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticComboboxType',
	fieldLabel : 'X轴统计类型',
	name : 'type',
	store : 'idatabase.Statistic.Type',
	valueField : 'value',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
