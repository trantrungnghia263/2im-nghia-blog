
$(document).ready(function(){
    $('#btn-getlink').click(function(){

        //Regex pattern
        var plink = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
        var pfshare = /^https:\/\/www\.fshare\.vn\/file\/[A-Za-z0-9_.]+$/g;

        //Loading button
        $('#btn-getlink').button('loading');
        $('#derect-link').val('Đang get link... Bình tĩnh, đợi tý nào.. =))');

        //Checklink
        var link = $('#link').val();
        if(jQuery.isEmptyObject( link )){
            message(
                'Không có link lấy gì get >.<',
                '<strong>CẢNH BÁO : </strong> Bạn chưa nhập link vào nên hệ thống không hệ thực hiện get link!',
                'alert-warning'
                )
        }
        else if(pfshare.test(link)) {
            getfshare();
        } else if(plink.test(link)){
            message(
                'Đang cập nhật...',
                '<strong>Xin lỗi...</strong> Link bạn nhập vào có thể chúng tôi không hỗ trợ hoặc đang cập nhật...',
                'alert-warning'
            )
        } else {
            message(
                'Cái này không phải link >.<',
                '<strong>CẢNH CÁO : </strong> Bạn nhập vào không phải link, đề nghị nghiêm túc...!',
                'alert-danger'
            )
        }
    });
});


//Post to fshare
function getfshare() {
    var url = "include/fshare.php";
    var data = {
        link : $('#link').val()
    };
    var success = function (result){
        message(
            result,
            '<strong>XONG! </strong> Copy vào trình duyệt hoạt idm để thực hiện tải file tốc độ cao...',
            'alert-success'
        )
    };
    var dataType = 'text';
    $.post(url, data, success, dataType);
}
function message(mesTest1,mesTest2,mesClass) {
    $('#derect-link').val(mesTest1);
    $('#btn-getlink').button('reset');
    $('.alert').html(mesTest2);
    $('.alert').addClass(mesClass);
}
