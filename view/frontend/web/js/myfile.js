require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'Kozar_AddBuyInClick/js/inputmask',
        'Magento_Catalog/product/view/validation',
        'Magento_Ui/js/view/messages'
    ],
    function (
        $,
        modal
    ) {
        function isEmail(email)
        {
            var EmailRegex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return EmailRegex.test(email);
        }

        function clearModal()
        {
            $("#number").val('');
            $("#name").val('');
            $("#emailaddress").val('');
        }

        $('#number').inputmask("(+380) 99-999-99-99");
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Купити в 1 клік',
            buttons: [
                {
                    text: $.mage.__('Купити в 1 клік'),
                    class: '',
                    click: function () {
                        var number = $("#number").val();
                        var name = $("#name").val();
                        var email = $("#emailaddress").val();
                        var qty = $("#qty").val();
                        var selected = $(".swatch-attribute-selected-option").text();
                        var sku = $('.value').html();
                        if (!isEmail(email)) {
                            alert('Будь ласка заповніть поле email');
                        } else {
                            $.ajax({
                                url: "http://magento.loc/buy/oneclick/product",
                                type: "POST",
                                data: {"number": number, "name": name, "email": email, "qty": qty, "selected": selected, "sku": sku},
                                showLoader: true,
                                cache: false,
                            })
                            clearModal();
                            this.closeModal();
                        }
                    }
            }
            ]
        };

        var popup = modal(options, $('#popup-modal'));
        $("#buy_now").on('click',function () {
            if ($('#product_addtocart_form').validation('isValid')) {
                $("#popup-modal").modal("openModal");
            }
        });

        $('#popup-modal').on('modalclosed', function() {
            clearModal();
        });

    }
);
