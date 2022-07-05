function handleSelectors() {
    let selectors = $('select');
    selectors.on('change.send', function (){
        let attributes = {'state': $(this).val()};
        let elem = $(this);
        sendAjax(
            'post',
            '/caller/current-calling?cottage=' + $(this).attr('data-cottage'),
            function (answer) {
                $('span#willNotComeSpan').text(answer.willNotCome);
                $('span#willComeSpan').text(answer.willCome);
                $('span#notAvailableSpan').text(answer.notAvailable);
                $('span#totalCalledSpan').text(answer.called);
                elem.parents('tr').attr('class', answer.color);
            },
            attributes
        )
    })
}

$(function () {
    handleSelectors();
});