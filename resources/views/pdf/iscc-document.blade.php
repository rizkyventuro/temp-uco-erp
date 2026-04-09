<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            ;
            background: #fff;
            font-family: 'Inter Tight', ui-sans-serif, system-ui, sans-serif,
                'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
                'Noto Color Emoji';
            font-size: 11.5px;
            line-height: 1.45;
            color: #000;
        }

        .page {
            width: 100%;
            padding: 24px 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            word-wrap: break-word;
        }

        td {
            border: 1px solid #bbb;
            padding: 5px 7px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .title-info {
            font-size: 13px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
        }

        .by-signing {
            margin-top: 12px;
            margin-bottom: 6px;
            font-size: 11.5px;
        }

        ol {
            padding-left: 20px;
            margin-top: 4px;
        }

        ol li {
            margin-bottom: 6px;
            text-align: justify;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            display: flex;
            justify-content: space-between;
        }

        .recipient-large {
            font-size: 18px;
            text-transform: uppercase;
        }

        .uppercase {
            text-transform: uppercase;
        }

        /* ol td should align top for readability */
        td.list-cell {
            vertical-align: top;
        }

        .signature-block {
            font-size: 13px;
            text-align: center;
        }
    </style>
</head>

<body style="  width: {{ $isPreview ? 'calc(100% - 56px)' : '100%' }};">

    <div class="page">

        <p class="title">
            ISCC CORSIA Self-Declaration for Points of Origin Generating Used Cooking Oil<br>(UCO)
        </p>

        <table>
            <tr>
                <td colspan="12" class="section-title">
                    Information about the Point of Origin (e.g. restaurant, catering facility, etc.):
                </td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Name</td>
                <td colspan="9">{{ $iscc['poo_name'] }}</td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Street address</td>
                <td colspan="9">{{ $iscc['poo_street'] }}</td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Postcode, location</td>
                <td colspan="9">{{ $iscc['poo_city'] }}</td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Country</td>
                <td colspan="9" class="uppercase">{{ $iscc['poo_country'] }}</td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Phone number</td>
                <td colspan="9">{{ $iscc['poo_phone'] }}</td>
            </tr>

            <tr>
                <td colspan="11" class="title-info">The amount of UCO produced by the Point of Origin is 10 (ten) or
                    more metric tons per month</td>
                <td colspan="1" style="text-align:center;">&#9746;</td>
            </tr>

            <tr>
                <td colspan="3" class="title-info">Recipient of the UCO<br>(Collecting Point)</td>
                <td colspan="9"><span class="recipient-large">{{ $iscc['recipient'] }}</span></td>
            </tr>

            <tr>
                <td colspan="12" class="section-title">
                    By signing this self-declaration, the signatory
                    acknowledges and confirms the following:
                </td>
            </tr>
            <tr>
                <td colspan="12" class="list-cell">
                    <ol>
                        <li>UCO refers to oil and fat of vegetable or animal origin which has been used to cook food for
                            human consumption. Deliveries of UCO covered under this self-declaration consist entirely of
                            UCO and are not mixed with any other oil or fat that doesn't comply with the definition of
                            UCO.</li>
                        <li>UCO covered under this self-declaration meets the definition of a "waste" under CORSIA: A
                            waste is any substance or object which the holder discards or intends or is required to
                            discard, excluding substances that have been intentionally modified or contaminated in order
                            to meet this definition.</li>
                        <li>Documentation of UCO quantities delivered is available.</li>
                        <li>Applicable national legislation regarding waste prevention and management (e.g. for
                            transport, supervision, etc.) is complied with.</li>
                        <li>The supplied material is generated exclusively by the signing point of origin.</li>
                        <li>Auditors from certification bodies or from ISCC (may be accompanied by a representative of
                            the Collecting Point) can examine on-site or by contacting the signatory (e.g., via
                            telephone) whether the statements made in this self-declaration are correct. The auditors of
                            the certification bodies may be accompanied by inspectors who monitor their activities.</li>
                        <li>The information on this self-declaration can be forwarded to and reviewed by the
                            certification body of the Collecting Point and by ISCC. Note: The certification body and
                            ISCC keep all data provided on this self-declaration confidential.</li>
                        <li>If audits of certification bodies or ISCC reveal that relevant ISCC requirements are not
                            complied with or declarations made in this self-declaration are not correct, and if the
                            Point of Origin is thereupon excluded as supplier of ISCC certified material, ISCC is
                            entitled to <b>publish the exclusion</b> of the Point of Origin on the ISCC website.</li>
                    </ol>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="height:60px;">{{ $iscc['place_date'] }}</td>
                <td colspan="5">
                    <div style="display: flex; justify-content:space-between">
                        <div>
                            {{ $iscc['signatory'] }}
                        </div>
                        <div>
                            Owner
                        </div>
                    </div>
                </td>
                <td colspan="4" style="text-align:center;">
                    @if(!empty($iscc['signature']))
                        <img src="{{ $iscc['signature'] }}" alt="Signature" style="max-height:50px; max-width:100%;">
                    @else
                        Digital
                    @endif
                </td>
            </tr>

            <tr class="signature-block">
                <td colspan="3">Place, date</td>
                <td colspan="5">Full name and function of signatory</td>
                <td colspan="4">Signature</td>
            </tr>

        </table>

        <div class="footer" style="margin-top: 100px">
            <table style="border: none; width: 100%;">
                <tr>
                    <td style="border: none; padding: 0; vertical-align: middle; width: 50%;">
                        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(public_path('assets/images/logo-iscc.svg'))) }}"
                            alt="ISCC System GmbH" style="height:52px;">
                    </td>
                    <td style="border: none; padding: 0; vertical-align: middle; text-align: right; font-size: 10px;">
                        <div>Version 2.1 – April 2024</div>
                        <div>Copyright &copy; ISCC System GmbH</div>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>

</html>
