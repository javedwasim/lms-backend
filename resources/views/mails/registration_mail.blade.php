<div style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;background-color:#f5f8fa;color:#74787e;height:100%;line-height:1.4;margin:0;width:100%!important;word-break:break-word">
   <table width="100%" cellpadding="0" cellspacing="0" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;background-color:#f5f8fa;margin:0;padding:0;width:100%">
      <tbody>
         <tr>
            <td align="center" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box">
               <table width="100%" cellpadding="0" cellspacing="0" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;margin:0;padding:0;width:100%">
                  <tbody> 
                     <tr>
                        <td style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;padding:25px 0;text-align:center">
                           <span style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;color:#bbbfc3;font-size:19px;font-weight:bold;text-decoration:none" target="_blank" >
                           <img src="{{ url('img/logo.png') }}" style="height: 91px;">
                           </span>
                        </td>
                     </tr>
                     <tr>
                        <td width="100%" cellpadding="0" cellspacing="0" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;background-color:#ffffff;border-bottom:1px solid #edeff2;border-top:1px solid #edeff2;margin:0;padding:0;width:100%">
                        	<div style="margin: 5% 15%">
                            	<p><b>Dear</b><span style="color:red"> {{ $receiver }} </span>,</p>
								<p>You have been successfully registered in {{env('APP_NAME')}}.</p>
								<p>Your Login details are as follows :-<br/>
								Login :    {{ $email }}<br/>  
								</p>
								Click here to verify your account {!! $verify_link !!}</span>
								<p>
								<b>With Regards,</b><br/> 
                        {{env('APP_NAME')}}
							</div>
                        </td>
                     </tr>
                     <tr>
                        <td style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box">
                           <table align="center" width="570" cellpadding="0" cellspacing="0" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;margin:0 auto;padding:0;text-align:center;width:570px">
                              <tbody>
                                 <tr>
                                    <td align="center" style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;padding:35px">
                                       <p style="font-family:Avenir,Helvetica,sans-serif;box-sizing:border-box;line-height:1.5em;margin-top:0;color:#aeaeae;font-size:12px;text-align:center">© 2021. {{env('APP_NAME')}} All rights reserved.</p>
                                    </td>
                                 </tr>
                              </tbody>
                           </table>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </td>
         </tr>
      </tbody>
   </table> 
</div>  
