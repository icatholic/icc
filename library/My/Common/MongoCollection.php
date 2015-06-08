<?php
/**
 * 扩展和限定基础类库操作
 * @author yangming
 * 
 * 使用说明：
 * 
 * 对于mongocollection的操作进行了规范，危险方法的drop remove均采用了伪删除的实现。
 * 删除操作时，remove实际上是添加了保留属性__REMOVED__设置为true
 * 添加操作时，额外添加了保留属性__CREATE_TIME__(创建时间) 和 __MODIFY_TIME__(修改时间) __REMOVED__：false
 * 更新操作时，将自动更新__MODIFY_TIME__
 * 查询操作时,count/find/findOne/findAndModify操作 ，查询条件将自动添加__REMOVED__:false参数，编码时，无需手动添加
 * 
 * 注意事项：
 * 
 * 1. findAndModify内部的操作update时，请手动添加__MODIFY_TIME__ __CREATE_TIME__ 原因详见mongodb的upsert操作说明，我想看完你就理解了
 * 2. group、aggregate操作因为涉及到里面诸多pipe细节，考虑到代码的可读性、简洁以及易用性，所以请手动处理__MODIFY_TIME__ __CREATE_TIME__ __REMOVED__ 三个保留参数
 * 3. 同理，对于db->command操作内部，诸如mapreduce等操作时，如涉及到数据修改，请注意以上三个参数的变更与保留，以免引起不必要的问题。
 * 
 */
namespace My\Common;

require __DIR__ . '/Mongo/Collection.php';

// if (version_compare(\MongoClient::VERSION, '1.5.0', '<')) {
//     require __DIR__ . '/Mongo/Collection1.4.php';
// } else {
//     require __DIR__ . '/Mongo/Collection1.5.php';
// }