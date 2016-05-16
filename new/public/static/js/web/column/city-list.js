
var CityList = function(){

    var cityListData = {
        "flag": 1,
        "msg": "",
        "data": {
            hotCity: [
                {Code: '110100', Name: '\u5317\u4eac', FirstLetter: 'B'},
                {Code: '310100', Name: '\u4e0a\u6d77', FirstLetter: 'S'},
                {Code: '500100', Name: '\u91cd\u5e86', FirstLetter: 'C'},
                {Code: '440300', Name: '\u6df1\u5733', FirstLetter: 'S'},
                {Code: '440100', Name: '\u5e7f\u5dde', FirstLetter: 'G'},
                {Code: '330100', Name: '\u676d\u5dde', FirstLetter: 'H'},
                {Code: '320100', Name: '\u5357\u4eac', FirstLetter: 'N'},
                {Code: '320500', Name: '\u82cf\u5dde', FirstLetter: 'S'},
                {Code: '120100', Name: '\u5929\u6d25', FirstLetter: 'T'},
                {Code: '510100', Name: '\u6210\u90fd', FirstLetter: 'C'},
                {Code: '360100', Name: '\u5357\u660c', FirstLetter: 'N'},
                {Code: '460200', Name: '\u4e09\u4e9a', FirstLetter: 'S'},
                {Code: '370200', Name: '\u9752\u5c9b', FirstLetter: 'Q'},
                {Code: '350200', Name: '\u53a6\u95e8', FirstLetter: 'X'},
                {Code: '610100', Name: '\u897f\u5b89', FirstLetter: 'X'},
                {Code: '430100', Name: '\u957f\u6c99', FirstLetter: 'C'}
            ],
            "Rows":[{"Code":"152900","Name":"\u963f\u62c9\u5584","RealCity":"\u963f\u62c9\u5584\u76df","FirstLetter":"A"},{"Code":"210300","Name":"\u978d\u5c71","RealCity":"\u978d\u5c71\u5e02","FirstLetter":"A"},{"Code":"340800","Name":"\u5b89\u5e86","RealCity":"\u5b89\u5e86\u5e02","FirstLetter":"A"},{"Code":"410500","Name":"\u5b89\u9633","RealCity":"\u5b89\u9633\u5e02","FirstLetter":"A"},{"Code":"513200","Name":"\u963f\u575d","RealCity":"\u963f\u575d\u85cf\u65cf\u7f8c\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"A"},{"Code":"520400","Name":"\u5b89\u987a","RealCity":"\u5b89\u987a\u5e02","FirstLetter":"A"},{"Code":"542500","Name":"\u963f\u91cc","RealCity":"\u963f\u91cc\u5730\u533a","FirstLetter":"A"},{"Code":"610900","Name":"\u5b89\u5eb7","RealCity":"\u5b89\u5eb7\u5e02","FirstLetter":"A"},{"Code":"652900","Name":"\u963f\u514b\u82cf","RealCity":"\u963f\u514b\u82cf\u5730\u533a","FirstLetter":"A"},{"Code":"654300","Name":"\u963f\u52d2\u6cf0","RealCity":"\u963f\u52d2\u6cf0\u5730\u533a","FirstLetter":"A"},{"Code":"130600","Name":"\u4fdd\u5b9a","RealCity":"\u4fdd\u5b9a\u5e02","FirstLetter":"B"},{"Code":"150200","Name":"\u5305\u5934","RealCity":"\u5305\u5934\u5e02","FirstLetter":"B"},{"Code":"150800","Name":"\u5df4\u5f66\u6dd6\u5c14","RealCity":"\u5df4\u5f66\u6dd6\u5c14\u5e02","FirstLetter":"B"},{"Code":"210500","Name":"\u672c\u6eaa","RealCity":"\u672c\u6eaa\u5e02","FirstLetter":"B"},{"Code":"220600","Name":"\u767d\u5c71","RealCity":"\u767d\u5c71\u5e02","FirstLetter":"B"},{"Code":"220800","Name":"\u767d\u57ce","RealCity":"\u767d\u57ce\u5e02","FirstLetter":"B"},{"Code":"340300","Name":"\u868c\u57e0","RealCity":"\u868c\u57e0\u5e02","FirstLetter":"B"},{"Code":"341600","Name":"\u4eb3\u5dde","RealCity":"\u4eb3\u5dde\u5e02","FirstLetter":"B"},{"Code":"371600","Name":"\u6ee8\u5dde","RealCity":"\u6ee8\u5dde\u5e02","FirstLetter":"B"},{"Code":"450500","Name":"\u5317\u6d77","RealCity":"\u5317\u6d77\u5e02","FirstLetter":"B"},{"Code":"451000","Name":"\u767e\u8272","RealCity":"\u767e\u8272\u5e02","FirstLetter":"B"},{"Code":"511900","Name":"\u5df4\u4e2d","RealCity":"\u5df4\u4e2d\u5e02","FirstLetter":"B"},{"Code":"520500","Name":"\u6bd5\u8282","RealCity":"\u6bd5\u8282\u5e02","FirstLetter":"B"},{"Code":"530500","Name":"\u4fdd\u5c71","RealCity":"\u4fdd\u5c71\u5e02","FirstLetter":"B"},{"Code":"610300","Name":"\u5b9d\u9e21","RealCity":"\u5b9d\u9e21\u5e02","FirstLetter":"B"},{"Code":"620400","Name":"\u767d\u94f6","RealCity":"\u767d\u94f6\u5e02","FirstLetter":"B"},{"Code":"652700","Name":"\u535a\u5c14\u5854\u62c9","RealCity":"\u535a\u5c14\u5854\u62c9\u8499\u53e4\u81ea\u6cbb\u5dde","FirstLetter":"B"},{"Code":"652800","Name":"\u5df4\u97f3\u90ed\u695e","RealCity":"\u5df4\u97f3\u90ed\u695e\u8499\u53e4\u81ea\u6cbb\u5dde","FirstLetter":"B"},{"Code":"130800","Name":"\u627f\u5fb7","RealCity":"\u627f\u5fb7\u5e02","FirstLetter":"C"},{"Code":"130900","Name":"\u6ca7\u5dde","RealCity":"\u6ca7\u5dde\u5e02","FirstLetter":"C"},{"Code":"140400","Name":"\u957f\u6cbb","RealCity":"\u957f\u6cbb\u5e02","FirstLetter":"C"},{"Code":"150400","Name":"\u8d64\u5cf0","RealCity":"\u8d64\u5cf0\u5e02","FirstLetter":"C"},{"Code":"211300","Name":"\u671d\u9633","RealCity":"\u671d\u9633\u5e02","FirstLetter":"C"},{"Code":"220100","Name":"\u957f\u6625","RealCity":"\u957f\u6625\u5e02","FirstLetter":"C"},{"Code":"320400","Name":"\u5e38\u5dde","RealCity":"\u5e38\u5dde\u5e02","FirstLetter":"C"},{"Code":"341100","Name":"\u6ec1\u5dde","RealCity":"\u6ec1\u5dde\u5e02","FirstLetter":"C"},{"Code":"341700","Name":"\u6c60\u5dde","RealCity":"\u6c60\u5dde\u5e02","FirstLetter":"C"},{"Code":"430100","Name":"\u957f\u6c99","RealCity":"\u957f\u6c99\u5e02","FirstLetter":"C"},{"Code":"430700","Name":"\u5e38\u5fb7","RealCity":"\u5e38\u5fb7\u5e02","FirstLetter":"C"},{"Code":"431000","Name":"\u90f4\u5dde","RealCity":"\u90f4\u5dde\u5e02","FirstLetter":"C"},{"Code":"445100","Name":"\u6f6e\u5dde","RealCity":"\u6f6e\u5dde\u5e02","FirstLetter":"C"},{"Code":"451400","Name":"\u5d07\u5de6","RealCity":"\u5d07\u5de6\u5e02","FirstLetter":"C"},{"Code":"510100","Name":"\u6210\u90fd","RealCity":"\u6210\u90fd\u5e02","FirstLetter":"C"},{"Code":"532300","Name":"\u695a\u96c4","RealCity":"\u695a\u96c4\u5f5d\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"C"},{"Code":"542100","Name":"\u660c\u90fd","RealCity":"\u660c\u90fd\u5730\u533a","FirstLetter":"C"},{"Code":"652300","Name":"\u660c\u5409","RealCity":"\u660c\u5409\u56de\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"C"},{"Code":"140200","Name":"\u5927\u540c","RealCity":"\u5927\u540c\u5e02","FirstLetter":"D"},{"Code":"210200","Name":"\u5927\u8fde","RealCity":"\u5927\u8fde\u5e02","FirstLetter":"D"},{"Code":"210600","Name":"\u4e39\u4e1c","RealCity":"\u4e39\u4e1c\u5e02","FirstLetter":"D"},{"Code":"230600","Name":"\u5927\u5e86","RealCity":"\u5927\u5e86\u5e02","FirstLetter":"D"},{"Code":"232700","Name":"\u5927\u5174\u5b89\u5cad","RealCity":"\u5927\u5174\u5b89\u5cad\u5730\u533a","FirstLetter":"D"},{"Code":"370500","Name":"\u4e1c\u8425","RealCity":"\u4e1c\u8425\u5e02","FirstLetter":"D"},{"Code":"371400","Name":"\u5fb7\u5dde","RealCity":"\u5fb7\u5dde\u5e02","FirstLetter":"D"},{"Code":"441900","Name":"\u4e1c\u839e","RealCity":"\u4e1c\u839e\u5e02","FirstLetter":"D"},{"Code":"510600","Name":"\u5fb7\u9633","RealCity":"\u5fb7\u9633\u5e02","FirstLetter":"D"},{"Code":"511700","Name":"\u8fbe\u5dde","RealCity":"\u8fbe\u5dde\u5e02","FirstLetter":"D"},{"Code":"532900","Name":"\u5927\u7406","RealCity":"\u5927\u7406\u767d\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"D"},{"Code":"533100","Name":"\u5fb7\u5b8f","RealCity":"\u5fb7\u5b8f\u50a3\u65cf\u666f\u9887\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"D"},{"Code":"533400","Name":"\u8fea\u5e86","RealCity":"\u8fea\u5e86\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"D"},{"Code":"621100","Name":"\u5b9a\u897f","RealCity":"\u5b9a\u897f\u5e02","FirstLetter":"D"},{"Code":"150600","Name":"\u9102\u5c14\u591a\u65af","RealCity":"\u9102\u5c14\u591a\u65af\u5e02","FirstLetter":"E"},{"Code":"420700","Name":"\u9102\u5dde","RealCity":"\u9102\u5dde\u5e02","FirstLetter":"E"},{"Code":"422800","Name":"\u6069\u65bd","RealCity":"\u6069\u65bd\u571f\u5bb6\u65cf\u82d7\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"E"},{"Code":"210400","Name":"\u629a\u987a","RealCity":"\u629a\u987a\u5e02","FirstLetter":"F"},{"Code":"210900","Name":"\u961c\u65b0","RealCity":"\u961c\u65b0\u5e02","FirstLetter":"F"},{"Code":"341200","Name":"\u961c\u9633","RealCity":"\u961c\u9633\u5e02","FirstLetter":"F"},{"Code":"350100","Name":"\u798f\u5dde","RealCity":"\u798f\u5dde\u5e02","FirstLetter":"F"},{"Code":"361000","Name":"\u629a\u5dde","RealCity":"\u629a\u5dde\u5e02","FirstLetter":"F"},{"Code":"440600","Name":"\u4f5b\u5c71","RealCity":"\u4f5b\u5c71\u5e02","FirstLetter":"F"},{"Code":"450600","Name":"\u9632\u57ce\u6e2f","RealCity":"\u9632\u57ce\u6e2f\u5e02","FirstLetter":"F"},{"Code":"360700","Name":"\u8d63\u5dde","RealCity":"\u8d63\u5dde\u5e02","FirstLetter":"G"},{"Code":"440100","Name":"\u5e7f\u5dde","RealCity":"\u5e7f\u5dde\u5e02","FirstLetter":"G"},{"Code":"450300","Name":"\u6842\u6797","RealCity":"\u6842\u6797\u5e02","FirstLetter":"G"},{"Code":"450800","Name":"\u8d35\u6e2f","RealCity":"\u8d35\u6e2f\u5e02","FirstLetter":"G"},{"Code":"510800","Name":"\u5e7f\u5143","RealCity":"\u5e7f\u5143\u5e02","FirstLetter":"G"},{"Code":"511600","Name":"\u5e7f\u5b89","RealCity":"\u5e7f\u5b89\u5e02","FirstLetter":"G"},{"Code":"513300","Name":"\u7518\u5b5c","RealCity":"\u7518\u5b5c\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"G"},{"Code":"520100","Name":"\u8d35\u9633","RealCity":"\u8d35\u9633\u5e02","FirstLetter":"G"},{"Code":"623000","Name":"\u7518\u5357","RealCity":"\u7518\u5357\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"G"},{"Code":"632600","Name":"\u679c\u6d1b","RealCity":"\u679c\u6d1b\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"G"},{"Code":"640400","Name":"\u56fa\u539f","RealCity":"\u56fa\u539f\u5e02","FirstLetter":"G"},{"Code":"130400","Name":"\u90af\u90f8","RealCity":"\u90af\u90f8\u5e02","FirstLetter":"H"},{"Code":"131100","Name":"\u8861\u6c34","RealCity":"\u8861\u6c34\u5e02","FirstLetter":"H"},{"Code":"150100","Name":"\u547c\u548c\u6d69\u7279","RealCity":"\u547c\u548c\u6d69\u7279\u5e02","FirstLetter":"H"},{"Code":"150700","Name":"\u547c\u4f26\u8d1d\u5c14","RealCity":"\u547c\u4f26\u8d1d\u5c14\u5e02","FirstLetter":"H"},{"Code":"211400","Name":"\u846b\u82a6\u5c9b","RealCity":"\u846b\u82a6\u5c9b\u5e02","FirstLetter":"H"},{"Code":"230100","Name":"\u54c8\u5c14\u6ee8","RealCity":"\u54c8\u5c14\u6ee8\u5e02","FirstLetter":"H"},{"Code":"230400","Name":"\u9e64\u5c97","RealCity":"\u9e64\u5c97\u5e02","FirstLetter":"H"},{"Code":"231100","Name":"\u9ed1\u6cb3","RealCity":"\u9ed1\u6cb3\u5e02","FirstLetter":"H"},{"Code":"320800","Name":"\u6dee\u5b89","RealCity":"\u6dee\u5b89\u5e02","FirstLetter":"H"},{"Code":"330100","Name":"\u676d\u5dde","RealCity":"\u676d\u5dde\u5e02","FirstLetter":"H"},{"Code":"330500","Name":"\u6e56\u5dde","RealCity":"\u6e56\u5dde\u5e02","FirstLetter":"H"},{"Code":"340100","Name":"\u5408\u80a5","RealCity":"\u5408\u80a5\u5e02","FirstLetter":"H"},{"Code":"340400","Name":"\u6dee\u5357","RealCity":"\u6dee\u5357\u5e02","FirstLetter":"H"},{"Code":"340600","Name":"\u6dee\u5317","RealCity":"\u6dee\u5317\u5e02","FirstLetter":"H"},{"Code":"341000","Name":"\u9ec4\u5c71","RealCity":"\u9ec4\u5c71\u5e02","FirstLetter":"H"},{"Code":"371700","Name":"\u83cf\u6cfd","RealCity":"\u83cf\u6cfd\u5e02","FirstLetter":"H"},{"Code":"410600","Name":"\u9e64\u58c1","RealCity":"\u9e64\u58c1\u5e02","FirstLetter":"H"},{"Code":"420200","Name":"\u9ec4\u77f3","RealCity":"\u9ec4\u77f3\u5e02","FirstLetter":"H"},{"Code":"421100","Name":"\u9ec4\u5188","RealCity":"\u9ec4\u5188\u5e02","FirstLetter":"H"},{"Code":"430400","Name":"\u8861\u9633","RealCity":"\u8861\u9633\u5e02","FirstLetter":"H"},{"Code":"431200","Name":"\u6000\u5316","RealCity":"\u6000\u5316\u5e02","FirstLetter":"H"},{"Code":"441300","Name":"\u60e0\u5dde","RealCity":"\u60e0\u5dde\u5e02","FirstLetter":"H"},{"Code":"441600","Name":"\u6cb3\u6e90","RealCity":"\u6cb3\u6e90\u5e02","FirstLetter":"H"},{"Code":"451100","Name":"\u8d3a\u5dde","RealCity":"\u8d3a\u5dde\u5e02","FirstLetter":"H"},{"Code":"451200","Name":"\u6cb3\u6c60","RealCity":"\u6cb3\u6c60\u5e02","FirstLetter":"H"},{"Code":"460100","Name":"\u6d77\u53e3","RealCity":"\u6d77\u53e3\u5e02","FirstLetter":"H"},{"Code":"532500","Name":"\u7ea2\u6cb3","RealCity":"\u7ea2\u6cb3\u54c8\u5c3c\u65cf\u5f5d\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"H"},{"Code":"610700","Name":"\u6c49\u4e2d","RealCity":"\u6c49\u4e2d\u5e02","FirstLetter":"H"},{"Code":"630200","Name":"\u6d77\u4e1c","RealCity":"\u6d77\u4e1c\u5e02","FirstLetter":"H"},{"Code":"632200","Name":"\u6d77\u5317","RealCity":"\u6d77\u5317\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"H"},{"Code":"632300","Name":"\u9ec4\u5357","RealCity":"\u9ec4\u5357\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"H"},{"Code":"632500","Name":"\u6d77\u5357","RealCity":"\u6d77\u5357\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"H"},{"Code":"632800","Name":"\u6d77\u897f","RealCity":"\u6d77\u897f\u8499\u53e4\u65cf\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"H"},{"Code":"652200","Name":"\u54c8\u5bc6","RealCity":"\u54c8\u5bc6\u5730\u533a","FirstLetter":"H"},{"Code":"653200","Name":"\u548c\u7530","RealCity":"\u548c\u7530\u5730\u533a","FirstLetter":"H"},{"Code":"140500","Name":"\u664b\u57ce","RealCity":"\u664b\u57ce\u5e02","FirstLetter":"J"},{"Code":"140700","Name":"\u664b\u4e2d","RealCity":"\u664b\u4e2d\u5e02","FirstLetter":"J"},{"Code":"210700","Name":"\u9526\u5dde","RealCity":"\u9526\u5dde\u5e02","FirstLetter":"J"},{"Code":"220200","Name":"\u5409\u6797","RealCity":"\u5409\u6797\u5e02","FirstLetter":"J"},{"Code":"230300","Name":"\u9e21\u897f","RealCity":"\u9e21\u897f\u5e02","FirstLetter":"J"},{"Code":"230800","Name":"\u4f73\u6728\u65af","RealCity":"\u4f73\u6728\u65af\u5e02","FirstLetter":"J"},{"Code":"330400","Name":"\u5609\u5174","RealCity":"\u5609\u5174\u5e02","FirstLetter":"J"},{"Code":"330700","Name":"\u91d1\u534e","RealCity":"\u91d1\u534e\u5e02","FirstLetter":"J"},{"Code":"360200","Name":"\u666f\u5fb7\u9547","RealCity":"\u666f\u5fb7\u9547\u5e02","FirstLetter":"J"},{"Code":"360400","Name":"\u4e5d\u6c5f","RealCity":"\u4e5d\u6c5f\u5e02","FirstLetter":"J"},{"Code":"360800","Name":"\u5409\u5b89","RealCity":"\u5409\u5b89\u5e02","FirstLetter":"J"},{"Code":"370100","Name":"\u6d4e\u5357","RealCity":"\u6d4e\u5357\u5e02","FirstLetter":"J"},{"Code":"370800","Name":"\u6d4e\u5b81","RealCity":"\u6d4e\u5b81\u5e02","FirstLetter":"J"},{"Code":"410800","Name":"\u7126\u4f5c","RealCity":"\u7126\u4f5c\u5e02","FirstLetter":"J"},{"Code":"420800","Name":"\u8346\u95e8","RealCity":"\u8346\u95e8\u5e02","FirstLetter":"J"},{"Code":"421000","Name":"\u8346\u5dde","RealCity":"\u8346\u5dde\u5e02","FirstLetter":"J"},{"Code":"440700","Name":"\u6c5f\u95e8","RealCity":"\u6c5f\u95e8\u5e02","FirstLetter":"J"},{"Code":"445200","Name":"\u63ed\u9633","RealCity":"\u63ed\u9633\u5e02","FirstLetter":"J"},{"Code":"620200","Name":"\u5609\u5cea\u5173","RealCity":"\u5609\u5cea\u5173\u5e02","FirstLetter":"J"},{"Code":"620300","Name":"\u91d1\u660c","RealCity":"\u91d1\u660c\u5e02","FirstLetter":"J"},{"Code":"620900","Name":"\u9152\u6cc9","RealCity":"\u9152\u6cc9\u5e02","FirstLetter":"J"},{"Code":"410200","Name":"\u5f00\u5c01","RealCity":"\u5f00\u5c01\u5e02","FirstLetter":"K"},{"Code":"530100","Name":"\u6606\u660e","RealCity":"\u6606\u660e\u5e02","FirstLetter":"K"},{"Code":"650200","Name":"\u514b\u62c9\u739b\u4f9d","RealCity":"\u514b\u62c9\u739b\u4f9d\u5e02","FirstLetter":"K"},{"Code":"653000","Name":"\u514b\u5b5c\u52d2","RealCity":"\u514b\u5b5c\u52d2\u82cf\u67ef\u5c14\u514b\u5b5c\u81ea\u6cbb\u5dde","FirstLetter":"K"},{"Code":"653100","Name":"\u5580\u4ec0","RealCity":"\u5580\u4ec0\u5730\u533a","FirstLetter":"K"},{"Code":"131000","Name":"\u5eca\u574a","RealCity":"\u5eca\u574a\u5e02","FirstLetter":"L"},{"Code":"141000","Name":"\u4e34\u6c7e","RealCity":"\u4e34\u6c7e\u5e02","FirstLetter":"L"},{"Code":"141100","Name":"\u5415\u6881","RealCity":"\u5415\u6881\u5e02","FirstLetter":"L"},{"Code":"211000","Name":"\u8fbd\u9633","RealCity":"\u8fbd\u9633\u5e02","FirstLetter":"L"},{"Code":"220400","Name":"\u8fbd\u6e90","RealCity":"\u8fbd\u6e90\u5e02","FirstLetter":"L"},{"Code":"320700","Name":"\u8fde\u4e91\u6e2f","RealCity":"\u8fde\u4e91\u6e2f\u5e02","FirstLetter":"L"},{"Code":"331100","Name":"\u4e3d\u6c34","RealCity":"\u4e3d\u6c34\u5e02","FirstLetter":"L"},{"Code":"341500","Name":"\u516d\u5b89","RealCity":"\u516d\u5b89\u5e02","FirstLetter":"L"},{"Code":"350800","Name":"\u9f99\u5ca9","RealCity":"\u9f99\u5ca9\u5e02","FirstLetter":"L"},{"Code":"371200","Name":"\u83b1\u829c","RealCity":"\u83b1\u829c\u5e02","FirstLetter":"L"},{"Code":"371300","Name":"\u4e34\u6c82","RealCity":"\u4e34\u6c82\u5e02","FirstLetter":"L"},{"Code":"371500","Name":"\u804a\u57ce","RealCity":"\u804a\u57ce\u5e02","FirstLetter":"L"},{"Code":"410300","Name":"\u6d1b\u9633","RealCity":"\u6d1b\u9633\u5e02","FirstLetter":"L"},{"Code":"411100","Name":"\u6f2f\u6cb3","RealCity":"\u6f2f\u6cb3\u5e02","FirstLetter":"L"},{"Code":"431300","Name":"\u5a04\u5e95","RealCity":"\u5a04\u5e95\u5e02","FirstLetter":"L"},{"Code":"450200","Name":"\u67f3\u5dde","RealCity":"\u67f3\u5dde\u5e02","FirstLetter":"L"},{"Code":"451300","Name":"\u6765\u5bbe","RealCity":"\u6765\u5bbe\u5e02","FirstLetter":"L"},{"Code":"510500","Name":"\u6cf8\u5dde","RealCity":"\u6cf8\u5dde\u5e02","FirstLetter":"L"},{"Code":"511100","Name":"\u4e50\u5c71","RealCity":"\u4e50\u5c71\u5e02","FirstLetter":"L"},{"Code":"513400","Name":"\u51c9\u5c71","RealCity":"\u51c9\u5c71\u5f5d\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"L"},{"Code":"520200","Name":"\u516d\u76d8\u6c34","RealCity":"\u516d\u76d8\u6c34\u5e02","FirstLetter":"L"},{"Code":"530700","Name":"\u4e3d\u6c5f","RealCity":"\u4e3d\u6c5f\u5e02","FirstLetter":"L"},{"Code":"530900","Name":"\u4e34\u6ca7","RealCity":"\u4e34\u6ca7\u5e02","FirstLetter":"L"},{"Code":"540100","Name":"\u62c9\u8428","RealCity":"\u62c9\u8428\u5e02","FirstLetter":"L"},{"Code":"542600","Name":"\u6797\u829d","RealCity":"\u6797\u829d\u5730\u533a","FirstLetter":"L"},{"Code":"620100","Name":"\u5170\u5dde","RealCity":"\u5170\u5dde\u5e02","FirstLetter":"L"},{"Code":"621200","Name":"\u9647\u5357","RealCity":"\u9647\u5357\u5e02","FirstLetter":"L"},{"Code":"622900","Name":"\u4e34\u590f","RealCity":"\u4e34\u590f\u56de\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"L"},{"Code":"231000","Name":"\u7261\u4e39\u6c5f","RealCity":"\u7261\u4e39\u6c5f\u5e02","FirstLetter":"M"},{"Code":"340500","Name":"\u9a6c\u978d\u5c71","RealCity":"\u9a6c\u978d\u5c71\u5e02","FirstLetter":"M"},{"Code":"440900","Name":"\u8302\u540d","RealCity":"\u8302\u540d\u5e02","FirstLetter":"M"},{"Code":"441400","Name":"\u6885\u5dde","RealCity":"\u6885\u5dde\u5e02","FirstLetter":"M"},{"Code":"510700","Name":"\u7ef5\u9633","RealCity":"\u7ef5\u9633\u5e02","FirstLetter":"M"},{"Code":"511400","Name":"\u7709\u5c71","RealCity":"\u7709\u5c71\u5e02","FirstLetter":"M"},{"Code":"320100","Name":"\u5357\u4eac","RealCity":"\u5357\u4eac\u5e02","FirstLetter":"N"},{"Code":"320600","Name":"\u5357\u901a","RealCity":"\u5357\u901a\u5e02","FirstLetter":"N"},{"Code":"330200","Name":"\u5b81\u6ce2","RealCity":"\u5b81\u6ce2\u5e02","FirstLetter":"N"},{"Code":"350700","Name":"\u5357\u5e73","RealCity":"\u5357\u5e73\u5e02","FirstLetter":"N"},{"Code":"350900","Name":"\u5b81\u5fb7","RealCity":"\u5b81\u5fb7\u5e02","FirstLetter":"N"},{"Code":"360100","Name":"\u5357\u660c","RealCity":"\u5357\u660c\u5e02","FirstLetter":"N"},{"Code":"411300","Name":"\u5357\u9633","RealCity":"\u5357\u9633\u5e02","FirstLetter":"N"},{"Code":"450100","Name":"\u5357\u5b81","RealCity":"\u5357\u5b81\u5e02","FirstLetter":"N"},{"Code":"511000","Name":"\u5185\u6c5f","RealCity":"\u5185\u6c5f\u5e02","FirstLetter":"N"},{"Code":"511300","Name":"\u5357\u5145","RealCity":"\u5357\u5145\u5e02","FirstLetter":"N"},{"Code":"533300","Name":"\u6012\u6c5f","RealCity":"\u6012\u6c5f\u5088\u50f3\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"N"},{"Code":"542400","Name":"\u90a3\u66f2","RealCity":"\u90a3\u66f2\u5730\u533a","FirstLetter":"N"},{"Code":"211100","Name":"\u76d8\u9526","RealCity":"\u76d8\u9526\u5e02","FirstLetter":"P"},{"Code":"350300","Name":"\u8386\u7530","RealCity":"\u8386\u7530\u5e02","FirstLetter":"P"},{"Code":"360300","Name":"\u840d\u4e61","RealCity":"\u840d\u4e61\u5e02","FirstLetter":"P"},{"Code":"410400","Name":"\u5e73\u9876\u5c71","RealCity":"\u5e73\u9876\u5c71\u5e02","FirstLetter":"P"},{"Code":"410900","Name":"\u6fee\u9633","RealCity":"\u6fee\u9633\u5e02","FirstLetter":"P"},{"Code":"510400","Name":"\u6500\u679d\u82b1","RealCity":"\u6500\u679d\u82b1\u5e02","FirstLetter":"P"},{"Code":"530800","Name":"\u666e\u6d31","RealCity":"\u666e\u6d31\u5e02","FirstLetter":"P"},{"Code":"620800","Name":"\u5e73\u51c9","RealCity":"\u5e73\u51c9\u5e02","FirstLetter":"P"},{"Code":"130300","Name":"\u79e6\u7687\u5c9b","RealCity":"\u79e6\u7687\u5c9b\u5e02","FirstLetter":"Q"},{"Code":"230200","Name":"\u9f50\u9f50\u54c8\u5c14","RealCity":"\u9f50\u9f50\u54c8\u5c14\u5e02","FirstLetter":"Q"},{"Code":"230900","Name":"\u4e03\u53f0\u6cb3","RealCity":"\u4e03\u53f0\u6cb3\u5e02","FirstLetter":"Q"},{"Code":"330800","Name":"\u8862\u5dde","RealCity":"\u8862\u5dde\u5e02","FirstLetter":"Q"},{"Code":"350500","Name":"\u6cc9\u5dde","RealCity":"\u6cc9\u5dde\u5e02","FirstLetter":"Q"},{"Code":"370200","Name":"\u9752\u5c9b","RealCity":"\u9752\u5c9b\u5e02","FirstLetter":"Q"},{"Code":"441800","Name":"\u6e05\u8fdc","RealCity":"\u6e05\u8fdc\u5e02","FirstLetter":"Q"},{"Code":"450700","Name":"\u94a6\u5dde","RealCity":"\u94a6\u5dde\u5e02","FirstLetter":"Q"},{"Code":"522300","Name":"\u9ed4\u897f\u5357","RealCity":"\u9ed4\u897f\u5357\u5e03\u4f9d\u65cf\u82d7\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"Q"},{"Code":"522600","Name":"\u9ed4\u4e1c\u5357","RealCity":"\u9ed4\u4e1c\u5357\u82d7\u65cf\u4f97\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"Q"},{"Code":"522700","Name":"\u9ed4\u5357","RealCity":"\u9ed4\u5357\u5e03\u4f9d\u65cf\u82d7\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"Q"},{"Code":"530300","Name":"\u66f2\u9756","RealCity":"\u66f2\u9756\u5e02","FirstLetter":"Q"},{"Code":"621000","Name":"\u5e86\u9633","RealCity":"\u5e86\u9633\u5e02","FirstLetter":"Q"},{"Code":"371100","Name":"\u65e5\u7167","RealCity":"\u65e5\u7167\u5e02","FirstLetter":"R"},{"Code":"542300","Name":"\u65e5\u5580\u5219","RealCity":"\u65e5\u5580\u5219\u5730\u533a","FirstLetter":"R"},{"Code":"110100","Name":"\u5317\u4eac","RealCity":"\u5317\u4eac\u5e02","FirstLetter":"S"},{"Code":"120100","Name":"\u5929\u6d25","RealCity":"\u5929\u6d25\u5e02","FirstLetter":"S"},{"Code":"130100","Name":"\u77f3\u5bb6\u5e84","RealCity":"\u77f3\u5bb6\u5e84\u5e02","FirstLetter":"S"},{"Code":"140600","Name":"\u6714\u5dde","RealCity":"\u6714\u5dde\u5e02","FirstLetter":"S"},{"Code":"210100","Name":"\u6c88\u9633","RealCity":"\u6c88\u9633\u5e02","FirstLetter":"S"},{"Code":"220300","Name":"\u56db\u5e73","RealCity":"\u56db\u5e73\u5e02","FirstLetter":"S"},{"Code":"220700","Name":"\u677e\u539f","RealCity":"\u677e\u539f\u5e02","FirstLetter":"S"},{"Code":"230500","Name":"\u53cc\u9e2d\u5c71","RealCity":"\u53cc\u9e2d\u5c71\u5e02","FirstLetter":"S"},{"Code":"231200","Name":"\u7ee5\u5316","RealCity":"\u7ee5\u5316\u5e02","FirstLetter":"S"},{"Code":"310100","Name":"\u4e0a\u6d77","RealCity":"\u4e0a\u6d77\u5e02","FirstLetter":"S"},{"Code":"320500","Name":"\u82cf\u5dde","RealCity":"\u82cf\u5dde\u5e02","FirstLetter":"S"},{"Code":"321300","Name":"\u5bbf\u8fc1","RealCity":"\u5bbf\u8fc1\u5e02","FirstLetter":"S"},{"Code":"330600","Name":"\u7ecd\u5174","RealCity":"\u7ecd\u5174\u5e02","FirstLetter":"S"},{"Code":"341300","Name":"\u5bbf\u5dde","RealCity":"\u5bbf\u5dde\u5e02","FirstLetter":"S"},{"Code":"350400","Name":"\u4e09\u660e","RealCity":"\u4e09\u660e\u5e02","FirstLetter":"S"},{"Code":"361100","Name":"\u4e0a\u9976","RealCity":"\u4e0a\u9976\u5e02","FirstLetter":"S"},{"Code":"411200","Name":"\u4e09\u95e8\u5ce1","RealCity":"\u4e09\u95e8\u5ce1\u5e02","FirstLetter":"S"},{"Code":"411400","Name":"\u5546\u4e18","RealCity":"\u5546\u4e18\u5e02","FirstLetter":"S"},{"Code":"420300","Name":"\u5341\u5830","RealCity":"\u5341\u5830\u5e02","FirstLetter":"S"},{"Code":"421300","Name":"\u968f\u5dde","RealCity":"\u968f\u5dde\u5e02","FirstLetter":"S"},{"Code":"430500","Name":"\u90b5\u9633","RealCity":"\u90b5\u9633\u5e02","FirstLetter":"S"},{"Code":"440200","Name":"\u97f6\u5173","RealCity":"\u97f6\u5173\u5e02","FirstLetter":"S"},{"Code":"440300","Name":"\u6df1\u5733","RealCity":"\u6df1\u5733\u5e02","FirstLetter":"S"},{"Code":"440500","Name":"\u6c55\u5934","RealCity":"\u6c55\u5934\u5e02","FirstLetter":"S"},{"Code":"441500","Name":"\u6c55\u5c3e","RealCity":"\u6c55\u5c3e\u5e02","FirstLetter":"S"},{"Code":"460200","Name":"\u4e09\u4e9a","RealCity":"\u4e09\u4e9a\u5e02","FirstLetter":"S"},{"Code":"460300","Name":"\u4e09\u6c99","RealCity":"\u4e09\u6c99\u5e02","FirstLetter":"S"},{"Code":"500100","Name":"\u91cd\u5e86","RealCity":"\u91cd\u5e86\u5e02","FirstLetter":"S"},{"Code":"510900","Name":"\u9042\u5b81","RealCity":"\u9042\u5b81\u5e02","FirstLetter":"S"},{"Code":"542200","Name":"\u5c71\u5357","RealCity":"\u5c71\u5357\u5730\u533a","FirstLetter":"S"},{"Code":"611000","Name":"\u5546\u6d1b","RealCity":"\u5546\u6d1b\u5e02","FirstLetter":"S"},{"Code":"640200","Name":"\u77f3\u5634\u5c71","RealCity":"\u77f3\u5634\u5c71\u5e02","FirstLetter":"S"},{"Code":"130200","Name":"\u5510\u5c71","RealCity":"\u5510\u5c71\u5e02","FirstLetter":"T"},{"Code":"140100","Name":"\u592a\u539f","RealCity":"\u592a\u539f\u5e02","FirstLetter":"T"},{"Code":"150500","Name":"\u901a\u8fbd","RealCity":"\u901a\u8fbd\u5e02","FirstLetter":"T"},{"Code":"211200","Name":"\u94c1\u5cad","RealCity":"\u94c1\u5cad\u5e02","FirstLetter":"T"},{"Code":"220500","Name":"\u901a\u5316","RealCity":"\u901a\u5316\u5e02","FirstLetter":"T"},{"Code":"321200","Name":"\u6cf0\u5dde","RealCity":"\u6cf0\u5dde\u5e02","FirstLetter":"T"},{"Code":"331000","Name":"\u53f0\u5dde","RealCity":"\u53f0\u5dde\u5e02","FirstLetter":"T"},{"Code":"340700","Name":"\u94dc\u9675","RealCity":"\u94dc\u9675\u5e02","FirstLetter":"T"},{"Code":"370900","Name":"\u6cf0\u5b89","RealCity":"\u6cf0\u5b89\u5e02","FirstLetter":"T"},{"Code":"520600","Name":"\u94dc\u4ec1","RealCity":"\u94dc\u4ec1\u5e02","FirstLetter":"T"},{"Code":"610200","Name":"\u94dc\u5ddd","RealCity":"\u94dc\u5ddd\u5e02","FirstLetter":"T"},{"Code":"620500","Name":"\u5929\u6c34","RealCity":"\u5929\u6c34\u5e02","FirstLetter":"T"},{"Code":"652100","Name":"\u5410\u9c81\u756a","RealCity":"\u5410\u9c81\u756a\u5730\u533a","FirstLetter":"T"},{"Code":"654200","Name":"\u5854\u57ce","RealCity":"\u5854\u57ce\u5730\u533a","FirstLetter":"T"},{"Code":"150300","Name":"\u4e4c\u6d77","RealCity":"\u4e4c\u6d77\u5e02","FirstLetter":"W"},{"Code":"150900","Name":"\u4e4c\u5170\u5bdf\u5e03","RealCity":"\u4e4c\u5170\u5bdf\u5e03\u5e02","FirstLetter":"W"},{"Code":"320200","Name":"\u65e0\u9521","RealCity":"\u65e0\u9521\u5e02","FirstLetter":"W"},{"Code":"330300","Name":"\u6e29\u5dde","RealCity":"\u6e29\u5dde\u5e02","FirstLetter":"W"},{"Code":"340200","Name":"\u829c\u6e56","RealCity":"\u829c\u6e56\u5e02","FirstLetter":"W"},{"Code":"370700","Name":"\u6f4d\u574a","RealCity":"\u6f4d\u574a\u5e02","FirstLetter":"W"},{"Code":"371000","Name":"\u5a01\u6d77","RealCity":"\u5a01\u6d77\u5e02","FirstLetter":"W"},{"Code":"420100","Name":"\u6b66\u6c49","RealCity":"\u6b66\u6c49\u5e02","FirstLetter":"W"},{"Code":"450400","Name":"\u68a7\u5dde","RealCity":"\u68a7\u5dde\u5e02","FirstLetter":"W"},{"Code":"532600","Name":"\u6587\u5c71","RealCity":"\u6587\u5c71\u58ee\u65cf\u82d7\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"W"},{"Code":"610500","Name":"\u6e2d\u5357","RealCity":"\u6e2d\u5357\u5e02","FirstLetter":"W"},{"Code":"620600","Name":"\u6b66\u5a01","RealCity":"\u6b66\u5a01\u5e02","FirstLetter":"W"},{"Code":"640300","Name":"\u5434\u5fe0","RealCity":"\u5434\u5fe0\u5e02","FirstLetter":"W"},{"Code":"650100","Name":"\u4e4c\u9c81\u6728\u9f50","RealCity":"\u4e4c\u9c81\u6728\u9f50\u5e02","FirstLetter":"W"},{"Code":"130500","Name":"\u90a2\u53f0","RealCity":"\u90a2\u53f0\u5e02","FirstLetter":"X"},{"Code":"140900","Name":"\u5ffb\u5dde","RealCity":"\u5ffb\u5dde\u5e02","FirstLetter":"X"},{"Code":"152200","Name":"\u5174\u5b89","RealCity":"\u5174\u5b89\u76df","FirstLetter":"X"},{"Code":"152500","Name":"\u9521\u6797\u90ed\u52d2","RealCity":"\u9521\u6797\u90ed\u52d2\u76df","FirstLetter":"X"},{"Code":"320300","Name":"\u5f90\u5dde","RealCity":"\u5f90\u5dde\u5e02","FirstLetter":"X"},{"Code":"341800","Name":"\u5ba3\u57ce","RealCity":"\u5ba3\u57ce\u5e02","FirstLetter":"X"},{"Code":"350200","Name":"\u53a6\u95e8","RealCity":"\u53a6\u95e8\u5e02","FirstLetter":"X"},{"Code":"360500","Name":"\u65b0\u4f59","RealCity":"\u65b0\u4f59\u5e02","FirstLetter":"X"},{"Code":"410700","Name":"\u65b0\u4e61","RealCity":"\u65b0\u4e61\u5e02","FirstLetter":"X"},{"Code":"411000","Name":"\u8bb8\u660c","RealCity":"\u8bb8\u660c\u5e02","FirstLetter":"X"},{"Code":"411500","Name":"\u4fe1\u9633","RealCity":"\u4fe1\u9633\u5e02","FirstLetter":"X"},{"Code":"420600","Name":"\u8944\u9633","RealCity":"\u8944\u9633\u5e02","FirstLetter":"X"},{"Code":"420900","Name":"\u5b5d\u611f","RealCity":"\u5b5d\u611f\u5e02","FirstLetter":"X"},{"Code":"421200","Name":"\u54b8\u5b81","RealCity":"\u54b8\u5b81\u5e02","FirstLetter":"X"},{"Code":"430300","Name":"\u6e58\u6f6d","RealCity":"\u6e58\u6f6d\u5e02","FirstLetter":"X"},{"Code":"433100","Name":"\u6e58\u897f","RealCity":"\u6e58\u897f\u571f\u5bb6\u65cf\u82d7\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"X"},{"Code":"532800","Name":"\u897f\u53cc\u7248\u7eb3","RealCity":"\u897f\u53cc\u7248\u7eb3\u50a3\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"X"},{"Code":"610100","Name":"\u897f\u5b89","RealCity":"\u897f\u5b89\u5e02","FirstLetter":"X"},{"Code":"610400","Name":"\u54b8\u9633","RealCity":"\u54b8\u9633\u5e02","FirstLetter":"X"},{"Code":"630100","Name":"\u897f\u5b81","RealCity":"\u897f\u5b81\u5e02","FirstLetter":"X"},{"Code":"140300","Name":"\u9633\u6cc9","RealCity":"\u9633\u6cc9\u5e02","FirstLetter":"Y"},{"Code":"140800","Name":"\u8fd0\u57ce","RealCity":"\u8fd0\u57ce\u5e02","FirstLetter":"Y"},{"Code":"210800","Name":"\u8425\u53e3","RealCity":"\u8425\u53e3\u5e02","FirstLetter":"Y"},{"Code":"222400","Name":"\u5ef6\u8fb9","RealCity":"\u5ef6\u8fb9\u671d\u9c9c\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"Y"},{"Code":"230700","Name":"\u4f0a\u6625","RealCity":"\u4f0a\u6625\u5e02","FirstLetter":"Y"},{"Code":"320900","Name":"\u76d0\u57ce","RealCity":"\u76d0\u57ce\u5e02","FirstLetter":"Y"},{"Code":"321000","Name":"\u626c\u5dde","RealCity":"\u626c\u5dde\u5e02","FirstLetter":"Y"},{"Code":"360600","Name":"\u9e70\u6f6d","RealCity":"\u9e70\u6f6d\u5e02","FirstLetter":"Y"},{"Code":"360900","Name":"\u5b9c\u6625","RealCity":"\u5b9c\u6625\u5e02","FirstLetter":"Y"},{"Code":"370600","Name":"\u70df\u53f0","RealCity":"\u70df\u53f0\u5e02","FirstLetter":"Y"},{"Code":"420500","Name":"\u5b9c\u660c","RealCity":"\u5b9c\u660c\u5e02","FirstLetter":"Y"},{"Code":"430600","Name":"\u5cb3\u9633","RealCity":"\u5cb3\u9633\u5e02","FirstLetter":"Y"},{"Code":"430900","Name":"\u76ca\u9633","RealCity":"\u76ca\u9633\u5e02","FirstLetter":"Y"},{"Code":"431100","Name":"\u6c38\u5dde","RealCity":"\u6c38\u5dde\u5e02","FirstLetter":"Y"},{"Code":"441700","Name":"\u9633\u6c5f","RealCity":"\u9633\u6c5f\u5e02","FirstLetter":"Y"},{"Code":"445300","Name":"\u4e91\u6d6e","RealCity":"\u4e91\u6d6e\u5e02","FirstLetter":"Y"},{"Code":"450900","Name":"\u7389\u6797","RealCity":"\u7389\u6797\u5e02","FirstLetter":"Y"},{"Code":"511500","Name":"\u5b9c\u5bbe","RealCity":"\u5b9c\u5bbe\u5e02","FirstLetter":"Y"},{"Code":"511800","Name":"\u96c5\u5b89","RealCity":"\u96c5\u5b89\u5e02","FirstLetter":"Y"},{"Code":"530400","Name":"\u7389\u6eaa","RealCity":"\u7389\u6eaa\u5e02","FirstLetter":"Y"},{"Code":"610600","Name":"\u5ef6\u5b89","RealCity":"\u5ef6\u5b89\u5e02","FirstLetter":"Y"},{"Code":"610800","Name":"\u6986\u6797","RealCity":"\u6986\u6797\u5e02","FirstLetter":"Y"},{"Code":"632700","Name":"\u7389\u6811","RealCity":"\u7389\u6811\u85cf\u65cf\u81ea\u6cbb\u5dde","FirstLetter":"Y"},{"Code":"640100","Name":"\u94f6\u5ddd","RealCity":"\u94f6\u5ddd\u5e02","FirstLetter":"Y"},{"Code":"654000","Name":"\u4f0a\u7281","RealCity":"\u4f0a\u7281\u54c8\u8428\u514b\u81ea\u6cbb\u5dde","FirstLetter":"Y"},{"Code":"130700","Name":"\u5f20\u5bb6\u53e3","RealCity":"\u5f20\u5bb6\u53e3\u5e02","FirstLetter":"Z"},{"Code":"321100","Name":"\u9547\u6c5f","RealCity":"\u9547\u6c5f\u5e02","FirstLetter":"Z"},{"Code":"330900","Name":"\u821f\u5c71","RealCity":"\u821f\u5c71\u5e02","FirstLetter":"Z"},{"Code":"350600","Name":"\u6f33\u5dde","RealCity":"\u6f33\u5dde\u5e02","FirstLetter":"Z"},{"Code":"370300","Name":"\u6dc4\u535a","RealCity":"\u6dc4\u535a\u5e02","FirstLetter":"Z"},{"Code":"370400","Name":"\u67a3\u5e84","RealCity":"\u67a3\u5e84\u5e02","FirstLetter":"Z"},{"Code":"410100","Name":"\u90d1\u5dde","RealCity":"\u90d1\u5dde\u5e02","FirstLetter":"Z"},{"Code":"411600","Name":"\u5468\u53e3","RealCity":"\u5468\u53e3\u5e02","FirstLetter":"Z"},{"Code":"411700","Name":"\u9a7b\u9a6c\u5e97","RealCity":"\u9a7b\u9a6c\u5e97\u5e02","FirstLetter":"Z"},{"Code":"430200","Name":"\u682a\u6d32","RealCity":"\u682a\u6d32\u5e02","FirstLetter":"Z"},{"Code":"430800","Name":"\u5f20\u5bb6\u754c","RealCity":"\u5f20\u5bb6\u754c\u5e02","FirstLetter":"Z"},{"Code":"440400","Name":"\u73e0\u6d77","RealCity":"\u73e0\u6d77\u5e02","FirstLetter":"Z"},{"Code":"440800","Name":"\u6e5b\u6c5f","RealCity":"\u6e5b\u6c5f\u5e02","FirstLetter":"Z"},{"Code":"441200","Name":"\u8087\u5e86","RealCity":"\u8087\u5e86\u5e02","FirstLetter":"Z"},{"Code":"442000","Name":"\u4e2d\u5c71","RealCity":"\u4e2d\u5c71\u5e02","FirstLetter":"Z"},{"Code":"510300","Name":"\u81ea\u8d21","RealCity":"\u81ea\u8d21\u5e02","FirstLetter":"Z"},{"Code":"512000","Name":"\u8d44\u9633","RealCity":"\u8d44\u9633\u5e02","FirstLetter":"Z"},{"Code":"520300","Name":"\u9075\u4e49","RealCity":"\u9075\u4e49\u5e02","FirstLetter":"Z"},{"Code":"530600","Name":"\u662d\u901a","RealCity":"\u662d\u901a\u5e02","FirstLetter":"Z"},{"Code":"620700","Name":"\u5f20\u6396","RealCity":"\u5f20\u6396\u5e02","FirstLetter":"Z"},{"Code":"640500","Name":"\u4e2d\u536b","RealCity":"\u4e2d\u536b\u5e02","FirstLetter":"Z"}]
        },
        "extra": {}
    };

    var box,
        $origin,
        cityList = {},
        superiorCity = {};

    var hotStr = '热门城市',
        ahStr = 'ABCDEFGH',
        ahStrLower = ahStr.toLowerCase(),
        ipStr = 'IJKLMNOP',
        ipStrLower = ipStr.toLowerCase(),
        qzStr = 'QRSTUVWXYZ',
        qzStrLower = qzStr.toLowerCase(),
        hotArr = cityListData.data.hotCity;

    cityList.init = function(id){
        if(!id) return ;
        var str;

        $origin = document.getElementById(id);

        var opt = getPos($origin),
            html = createHtml();

        box = document.createElement('div');
        box.style.cssText = 'position: absolute; left: '+(opt.left - 200)+'px ; top: '+(opt.top + 30)+'px;';
        box.className = 'citybox hide';
        box.innerHTML = html;
        document.body.appendChild(box);

        //addEvent(box);
        cityList.init = null;

    }

    cityList.setPos = function(){
        var opt = getPos($origin);
        box.style.cssText = 'position: absolute; left: '+(opt.left - 200)+'px ; top: '+opt.top+'px;';
    }

    function addEvent(elem){        // 点击动作暂不处理
        var fn = function(e){
            var target;
            e = e || window.event;
            target = e.target || e.srcElement;
        }
        elem.addEventListener('click', fn, false)

    }

    function createHtml(){
        handleCity(); // 处理数据到详细分类
        var str = '',

            d, i, len;

        str += '<span class="city-close">x</span>';

        str += '<ul><li class="on">'+ hotStr +'</li><li>'+ ahStr +'</li><li>'+ ipStr +'</li><li>'+ qzStr +'</li></ul>';
        str += '<div class="hotcity">';

        // 热门城市
        str += '<div class="hot hottab"><dl><dt>&nbsp;</dt><dd>';
        for(i=0, d=superiorCity.hot,len=d.length; i<len; i++){
            str+= '<a data-code="'+ d[i].Code +'">'+ d[i].Name +'</a>';
        }
        str += '</dd></dl></div>';

        var ahStrArr = [],
            ipStrArr = [],
            qzStrArr = [];
        separateLetter(ahStrArr, ahStr);
        separateLetter(ipStrArr, ipStr);
        separateLetter(qzStrArr, qzStr);

        // A-H城市
        str += '<div class="ABCDEFGH citytab hide">';
        for(i=0,len=ahStrArr.length; i<len; i++){
            str += '<dl><dt>'+ ahStrArr[i] +'</dt><dd>';
            for(var jD,j=0,jLen=superiorCity.AH[ahStrArr[i]].length || 0; j<jLen; j++){
                jD = superiorCity.AH[ahStrArr[i]][j];
                str += '<a data-code="' + jD.code + '">'+ jD.name +'</a>';
            }
            str += '</dd></dl>';
        }
        str += '</div>';

        // I-P城市
        str += '<div class="IJKLMNOP citytab hide">';
        for(i=0,len=ipStrArr.length; i<len; i++){
            str += '<dl><dt>'+ ipStrArr[i] +'</dt><dd>';
            for(var jD,j=0,jLen=superiorCity.IP[ipStrArr[i]] ? superiorCity.IP[ipStrArr[i]].length : 0; j<jLen; j++){
                jD = superiorCity.IP[ipStrArr[i]][j];
                str += '<a data-code="' + jD.code + '">'+ jD.name +'</a>';
            }
            str += '</dd></dl>';
        }
        str += '</div>';

        // Q-Z城市
        str += '<div class="QRSTUVWXYZ citytab hide">';
        for(i=0,len=qzStrArr.length; i<len; i++){
            str += '<dl><dt>'+ qzStrArr[i] +'</dt><dd>';
            for(var jD,j=0,jLen=superiorCity.QZ[qzStrArr[i]] ? superiorCity.QZ[qzStrArr[i]].length : 0; j<jLen; j++){
                jD = superiorCity.QZ[qzStrArr[i]][j];
                str += '<a data-code="' + jD.code + '">'+ jD.name +'</a>';
            }
            str += '</dd></dl>';
        }
        str += '</div>';

        str += '</div>';
        return str;
    }


    function handleCity(){
        superiorCity.hot = cityListData.data.hotCity;

        superiorCity.AH = [];
        superiorCity.IP = [];
        superiorCity.QZ = [];

        var d, i= 0, len, s, iD;
        d = cityListData.data.Rows;
        for(len= d.length; i<len; i++){

            iD = d[i];
            s = iD.FirstLetter.toLowerCase();
            if(ahStrLower.indexOf(s) > -1){
                if(superiorCity.AH[iD.FirstLetter] == undefined){
                    superiorCity.AH[iD.FirstLetter] = [];
                }
                superiorCity.AH[iD.FirstLetter].push({code: iD.Code, name: iD.Name});

            }else if(ipStrLower.indexOf(s) > -1){
                if(superiorCity.IP[iD.FirstLetter] == undefined){
                    superiorCity.IP[iD.FirstLetter] = [];
                }
                superiorCity.IP[iD.FirstLetter].push({code: iD.Code, name: iD.Name});

            }else if(qzStrLower.indexOf(s) > -1){
                if(superiorCity.QZ[iD.FirstLetter] == undefined){
                    superiorCity.QZ[iD.FirstLetter] = [];
                }
                superiorCity.QZ[iD.FirstLetter].push({code: iD.Code, name: iD.Name});

            }else{}
        }
    }

    function separateLetter(arr, s){    // 字符转数据
        if(!arr || !s) return ;

        for(var i= 0,len= s.length; i<len; i++){
            arr.push(s.substr(i, 1));
        }
    }

    function getPos(obj){
        if(obj.nodeType != 1) return ;
        var pos = {}, left = 0, top = 0,
            objCopy = obj;

        while(objCopy){
            left += objCopy.offsetLeft;
            objCopy = objCopy.offsetParent;
        }

        objCopy = obj;
        while(objCopy){
            top += objCopy.offsetTop;
            objCopy = objCopy.offsetParent;
        }

        pos.left = left, pos.top = top;


        return pos;

    }
    return cityList;
}();


