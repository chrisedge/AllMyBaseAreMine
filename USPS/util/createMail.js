var db = require('diskdb');
	
db = db.connect('collections', ['inbox']);

/*
[{"postmarkTimestamp":"1427361869",
  "deliveryTimestamp":"1427444669",
  "services":[{
    "readReceipt":"",
    "certified":"",
    "returnPostagePaid":""
  }],
  "messageData":"",
  "physicalAddress":"",
  "fromAlias":"",
  "toAlias":"",
  "toAliasID":"",
  "fromSubscriberID":"",
  "toSubscriberID":""
}]
*/

var mail = {
	postmarkTimestamp: "1427361869",
	deliveryTimestamp: "1427444669",
	services: {
		readReceipt: "",
		certified: "",
		returnPostagePaid: ""
	},
	messageData: "PHA+PGltZyBjbGFzcz0iZnItZmluIGZyLWRpYiIgYWx0PSJJbWFnZSB0aXRsZSIgc3JjPSJkYXRhOmltYWdlL3BuZztiYXNlNjQsaVZCT1J3MEtHZ29BQUFBTlNVaEVVZ0FBQUVnQUFBQklDQVlBQUFCVjdiTkhBQUFBQkdkQlRVRUFBTEdQQy94aEJRQUFBQ0JqU0ZKTkFBQjZKUUFBZ0lNQUFQbi9BQUNBNlFBQWRUQUFBT3BnQUFBNm1BQUFGMitTWDhWR0FBQUFCbUpMUjBRQS93RC9BUCtndmFlVEFBQUFDWEJJV1hNQUFBcndBQUFLOEFGQ3JEU1lBQUFNRGtsRVFWUjQydTJjV1d4YzEzbkhmK2ZjWllZejVIQVJSVWtVcVlXMnRWaGJiTWQxTFNtMlpEdFI3TmlSNjhaSmlqcUFrd0p0SDlxSDFpbUNva0JSOUtGOU1kQytGRWpqSWtXQklFaUFJbkRTMXE1ZHRMWTJTckpsV1paRWlscTRTaVFsYmlJNU01dzc5OTV6VGgvdURFbHQxa0tKUTRyK0E0T1p1ZHZjKzV2dmZOOTN2blBQaFMvMGhXWWljWnZieHFRUUsySkNiSENFYkJDQzhsSmZ3SzFJUXlZdzVyeXY5WEZ0VEE4UTNrMUFFbGhXWjluUFBoeUx2N1FoRnQveVNEeFIxK0M0NVVuSHNSQUNUS2tSZk00RkNrRk9DdFduVmVaVGI2TC81RVQyYUVzMjgzWi8zdnRmWUdRbWdDUlFrUlR5bTc4ZGkzOS9kNkppNjFObHlWaUQ3V0FaZ3pEbXRzeXZsSUNrWllGdG8xMlhTN2JGUGorWCs5WHc0SWQ3UjRaL1BCNEcvd1dvMndWa0EydFgyYzRmdlo1TWZXZDNXVVhkU3R0R0FMclVWM3luTWdhRXdMWXMzR1NTM25pTVgwK2tMN3pWMi9OV1czcjhMYUQvZXJ0WjExbm1BSnZXMk83ZnZaR3ErZjNYa3FueVdzdkNNS2RiMHMwbElsdlF4aERrODFUNkFWOU9WYVlXcFNwM3RPZHpTd2Z5M2tFZ2N6TkFEdEN3d3JMLzRvZVZOYTkrTzFFaEhDSG1ONWdiU0drTk9ZOHR5WEpSV1ZXMXNTMlh6UXpsOC91NHlnNm1BNUxBNHJnUXIvOUpSZlVQdnBkTWxkMnZjSW95eHFBOWo0MFZLU0hLazJ1YUw0OTAvSEhUUTIySFI0YXVDeWdCUExFem5uemp6MVBWalRYU3VxL2hUSWNrODNsV1ZkZWtPclNxL2RldTl2OEdzc1gxY2hxb3hWVlNmdTNsc3ZJMXl5MTcvanJqTzFBUWhpekpaTmxkdTJSYlF5SzU0NGRyTjB5dUt3S0tBNnZYMnU1ejIrTmw5a0t3bkt2bFp6SnNkMlB4OVJXcDc3MTV1aVZXRE8rU0tOUW5nVTJiSFhmNU1zdGVFRTNyYW9WS1Vlc0hQRnFlV2crc0ZvVU1TQlplS1J2eDBBWW5WbTZYK2t4TEpXT1Fuc2VHUkhKUm1XMnZLMmFJUlVCSlM3QzAwWFpjT1pNZm1jOFNBbnlmcGJhVHNxVmNYbHhjQkJRWGtFaktZb3RibUZKS0VSZENTa1J5T2lBQldBSnNhMEhqaVVLK2lCendaUHBUYkZHQ2hjMW1tcTRNVVF2VzVkeXF2Z0IwRTkyZHFLNExlYmNRazczbSswVXpCK1E0MkZzMmdlK2plczVqMGhrSVE5Q200Tm5tTjdTWkFUSUdVVlZKMlkvZXdGcGVqenB6Rm5YNkRLcTlFM1grQXFiL0lucDBGSk5PUTFBb0F4ZGh6Uk5vTTdZZ1dWbUp0YndlMmRpQWJHekFlWFluQkFGNmRBd3pOSVR1N1VkMWRLSTZPOUVkWGFqdTg1aXhVWXlYaHlDSW1tZlJ5dVlndEJsYmtGeFNoeWkvYW5ERGNaQ0xhMkZ4TGRiNmRUaG1Cd1FCSnBkRER3eWl1M3BRblYyb2prNTBkdys2L3lKNmVCZ3puZ2Fsb3ZJb3pBbHJtN0VGaVNWMUVJdmRaQ01Ccm90d1hhektTcXlISHNRQjhIMzBlQm96TW9MdTYwZDFkS0hhTzlDZFhaRS9HeHZEWkNmQTl5ZHJ5ck1OYStZV2xFb2huRHM4ak9zaWF4ZEI3U0tzTlEvaDdIZ0tRb1h4UFBURlMraWU4Nml1THRTWnMraXVIblJmUDNwd0NKUE5ScFpXaEg4UG9jME1rSlNJNmlxdzcySU53TFlRNVVtc0I1dXdIbXpDNFdsTVBnK1pESHA0QkgyaEYzWG1MT0dKRmxSTEs3cTNINVBMVGNHYWE0Qk1kb0t3cFRVSzY1OG5NZTJEaU42Rm1QNmRLTXNYQXFRRVN4YmVMVVFzaGtnbWtDc2FzVmF2S2xoYWlMclFTM2prS1A2Nzd4TWVQQlNsR0ZmODVzeXRhOFpOTFAvelgrTC81enUzdHYwTlQ3WUF5UlJBU29tUUJVQlNndU1na2tsRVJSSlJXWWxjc2dUWjJCQloyUGF0T00vc0lIajNmYnlmL1R4eTlFS0ExcGhNSnZwZWpKU3pEZ2d3NlhTVTU5eUdSRmxad2JGUHM3cXJETkJjL1duNmVnRllOcklxaFZ5NWt0Z3J1M0ZmL0RyV3crdWk1aVlFUWtxTTUrRy84eDcrMjcrSjBvcFNBTHJ0ZjBZSzNOM2Z3SDNsZDBCUEcvSFYwNFltaTVZMENjWk1oZjdwMGhxVDk5RTk1OG4rNVY5SGNLUkVDQW5sU1p5bnYwTFpuLzBwNXZKbC9IZmVBOHZpZGpYN0ZkWlFZZElaN00wYkVlWEpPenFFdm5ncGltd2psOUh0SFlRblc5QjkvUWpIQmJzd0NueXVIWFdpQmZ0TG01Rk5xNk0vNFBiNWxBQVFFSDUyQXRYZWpyMWw4NTBkd0xZUjFkVllsWlhZNjlZUSsvYnZRandlbGZ4RVZOcFNaODZTLzhsUFFTbk13R0RwZk5CdFMwcjB4VXVFSHgzQjNyenBqazVjMWk2SzhpZUlBR1Fub3VibGVXQTd5SnBxMUVRT2U5dVRtTEZ4Z2tNZlJWRnhYZ0FDQ0FLQ0E0ZUlmZWRWUktyaTF2Y3J3TkQ5RjFFZEhhaXo3ZWlPVHZUb1dOU3ZpOGV3R2h0eFgzNEpQVGlJcy9VSjhyLzRkL1NGM25sa1FRQkNvRnBhVVdmUFlqLzI2T2R2cTNYa2M5cE9FeDc5TkVvUXozVUFJQmZYSXV2cnNSL1pqTFZxRmJKK0dYSjVQYUl5aFZ5MmxQRFljZnovZU9kS3B6OWZBT21oWWNKREgxOGZVQkNnaDRaUnJhY0k5amNUSEQ2Q0dSZ0ExOFZldDRiNGE3K0g5Y2htck1aR1JFMDFJaDZmc2hDbG9tTi9kQVR2eDIraEJ3WkEzbm15V0xweHdueWVvUGtRc2UrK2lsaFVBNERKWkFpUG55VFllNEN3K1JDcXZSMlJTR0J2MllUOXJaZXh2L3dvMWdNUElCSmwxelFaay9OUXA5b0k5aDBnMkxzZjFYSUtrOG1VT0pPZWlhUkV0YllSdHAzQldyV0NZSDh6L252L2d6cjZHU1lNc0pwV0UvK0QxM0YyUG8zMVFCT2lvdnhhS1BrOCtrSWY0WkZQQ0Q3Y1MzajBHUHJTUU9TUHBMd3JmYlBTQVJJQ1BUSkM3czEvQUQ5QW5UNkRLRTlpYjNzU2Q5ZFhzUjkvRExta0xyclFLNmdZek9nbzRiSGpCQi9zSlRoMEdOM2VpZkc4U2ZEWDdETXZBUmtEWVVoNDlCald5aFhFWHZzdTd2TzdzRFkrakVoZW0wQ2FiQmJWM2tIWWZKamdnejJFcmFjd28yTlJQK3N1UXlrdElCTjFHMFNpREd2ZFd0d1hkazAySXh6bnltM0RFSDN4RXNIaGo2TW05T2xuVWNnT2dxbWUrajBDTS91QWltQXF5ckcvdEFYM2hhL2pQTGNUdVd6cHRiNWw1REpoTVlMdDJZOCtkdzZUODZKajNFTnJLUTJnUWlkVFZGVmhQLzRZc2QwdllqLzVSRlN6bm5haEpqdUI3dW9pMk5kTXNPOEE2bFFiZW1nNHFod1dIVzRKYXRQM0RsQ2hCaU5xRitGczM0Yjc0dk00VzU5QXBGSlQyeWlGN3UyTGlsNGY3aVU4Y2hUZDJ4ZU5xOEdzVzh2c0FESUdMQ3NhQW5wcU8rNUxMMFE5OTRxS3lmV1RUZWlEUFlRSEQ2TTZ1Nk9hMGl6NWxka0hWUEF2dUM3V0EwMjR6KzNFMmZWVnJBM3JFYTRiUWNubDBPYzZDSm9QUm9uZ3lSYk04TWpVYU1VY2duTDNBRTFHcEFUVytyVzR6Ky9DK2RxeldLdFdSc1dwSUVCMWRSTis4aW5CaDN0Um54eEQ5ZmREUGw5U3Z6STdnSXhCVkZkaFAxS0lTRi9aaHF4ZkZuVXVoNFpSSjFvSTl1NGpPUFF4dXFzN0dxb3BBcm1EeXQ3OEFXUU14R0s0eit6QWZmVVZuQ2QvQzFGUmdVbW5DUTkvSEVXaEE4Mm9zKzJZOGZHcEtEUkhtOUE5QVNUckZsUDJvemVRRGZYb3ptNkNBd2NKOXUxSG5XeEZEdzVOUVlGNVpTMTNCMUFCVW5qOEJPRy8vSlJ3LzBGVU1idUZlVzB0ZHdkUW9XUTY4VmQvTXpVRWZKOUJ1ZUp5cDM4eEJzeXQzR2V2VkpTM0ZGUC8rMG5tQmpkeEdrQ3BXNTdyT3VmRDh4MUxxWWhHZ2RNVUlHTk10bGhUV2NCU25nZGFxeUloU2NGNE5PUUhWSDVCVG1RcFNrakJhSkRUeXBncjVvc1pvanNJeHpwVkdIaUJYK3J6TEpsQ1krankvYlJ2VEcvUlVDVFJST2FzZ1F1dFlaQWJ5bVVYN0MzM1l5cmdSQzQ3NUd2ZFZsd21pZWFNcDRIMjB6QjhNcHNtQ0lOU24rdXNLMVFoYmQ0RUxiN2ZCblJPQjJRS2dMcDc0ZGcrNWV1KzlDakdMQnh2Wkl4aDBNdlNySHl2MC9kLzlvYzFOZm5pMVJmN0FScVFJYVE4ZUhoRjRLY1dJMG5FNDZVKzkxbWdBeU9aY1k2cFBMOEtnajB0K2Z5Ym4rUnlrMDdhbXRxTUVMQkdvTmFGcHJvZzc1UWpTYml4K3pibk1jWXduQjduakovaGZVSGZ1NTczdDd1VHlTT3QvbFNnbXQ2VERBRmZnOThIeXhPWXhvcThoOVNLaEJQRHZvOHlaa0UwMDdrdmZabU9YSWIvRStqZitQNC9EbXY5azFiZnYrR0RCVFNRQnlZOHlKK0hoamltcml6d3lYcFpwQUZYU2l6TG1weGNOdDllQUg3Z016eVJwbjFzaUc0L3h4Nk0rYlZTditqUit1K0I4ZXZCdlBwN0diQWFlS1lHdnZVY1BMWWRrc3VBQ3RzaDVjU29zbHdTOFFTMlBUOUtHV0dvbVBBbUdGTSs0NEhQdUFyb0F6NHlwdjhEK0xjKytDZmd3dlgyRlRkWUZnZVdBeHNkZVA1QmVHWTdyTjRBMWlMQUJTd2hFZlBBTjBVM3p4cENyZkdKSGhqVUN2bkRjS0FOL3RtRHR3SC84L2EvMFhJSHFBUWFnSTBKMkZvUGo2K0NGVTJRcWdXM2pPSVV6N2tyQVhpZ2g4RHJnc0h6Y0xJWDNoNkRkNEUrYnZKUW01dVpnQ0F5bUFxZ0JsZ0tyTGFneVlJYUVhMmJ5eklDbElGeEJkMEtqaGs0RFl4eGk0OUN1dFUySW9pU1NwZklzdWJMQkdsRDFGTUlpSnFSWnA0L0Jtbk82ZjhCWGpCZE1YSHQ3ZndBQUFBbGRFVllkR1JoZEdVNlkzSmxZWFJsQURJd01UTXRNRFF0TUROVU1UYzZNVGc2TURJck1EZzZNRERqcndwREFBQUFKWFJGV0hSa1lYUmxPbTF2WkdsbWVRQXlNREV5TFRBM0xURTFWREl4T2pRNE9qUXpLekE0T2pBd2ZWV2hPd0FBQUUxMFJWaDBjMjltZEhkaGNtVUFTVzFoWjJWTllXZHBZMnNnTmk0NExqZ3ROeUJSTVRZZ2VEZzJYelkwSURJd01UUXRNREl0TWpnZ2FIUjBjRG92TDNkM2R5NXBiV0ZuWlcxaFoybGpheTV2Y21kWnBGOS9BQUFBR0hSRldIUlVhSFZ0WWpvNlJHOWpkVzFsYm5RNk9sQmhaMlZ6QURHbi83c3ZBQUFBR0hSRldIUlVhSFZ0WWpvNlNXMWhaMlU2T2tobGFXZG9kQUF4TVRSaDUxNW9BQUFBRjNSRldIUlVhSFZ0WWpvNlNXMWhaMlU2T2xkcFpIUm9BREV4TlBJV0RqVUFBQUFaZEVWWWRGUm9kVzFpT2pwTmFXMWxkSGx3WlFCcGJXRm5aUzl3Ym1jL3NsWk9BQUFBRjNSRldIUlVhSFZ0WWpvNlRWUnBiV1VBTVRNME1qTTJNREV5TS9oaysvNEFBQUFUZEVWWWRGUm9kVzFpT2pwVGFYcGxBRGN1TURKTFFrSzl1SDBBQUFBQVluUkZXSFJVYUhWdFlqbzZWVkpKQUdacGJHVTZMeTh2YUc5dFpTOW1kSEF2TVRVeU1DOWxZWE41YVdOdmJpNWpiaTlsWVhONWFXTnZiaTVqYmk5alpHNHRhVzFuTG1WaGMzbHBZMjl1TG1OdUwzQnVaeTh4TURjMk55OHhNRGMyTnprMUxuQnVaOUlMbzNBQUFBQUFTVVZPUks1Q1lJST0iIHdpZHRoPSIxOTUiIHRpdGxlPSJJbWFnZSB0aXRsZSI+PC9wPjxoMj5UaGlzIGlzIHRleHQgYWxyZWFkeSBoZXJlLjwvaDI+",
	fromPhysicalAddress: "Multiple\r\nLines\r\nHere\r\nZipcode",
	fromFullName: "Jane Doe",
	fromAlias: "jane_doe",
	fromSubscriberID: "0015551212",
	toPhysicalAddress: "",
	toFullName: "",
	toAlias: "chris_edge",
	toSubscriberID: "0005551212",
	toAliasID: "3b23269616914911801acd68bd178dd8",
}

db.inbox.save(mail);